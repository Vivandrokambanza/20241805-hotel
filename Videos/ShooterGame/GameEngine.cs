namespace ShooterGame;

public class GameEngine : IDisposable
{
    // ── Constantes ───────────────────────────────────────────────────────────
    private const int WorldW  = 90;
    private const int WorldH  = 40;
    private const int ViewW   = Renderer.ViewW;
    private const int ViewH   = Renderer.ViewH;

    // ── Subsistemas ──────────────────────────────────────────────────────────
    private readonly World        _world    = new(WorldW, WorldH);
    private readonly Combat       _combat   = new();
    private readonly Renderer     _renderer = new();
    private readonly ConsoleInput _input    = new();
    private readonly SoundSystem  _sound    = new();

    // ── Conta do jogador (pode ser null se jogou sem login) ───────────────────
    private readonly Database?    _db;
    private readonly UserAccount? _user;

    // ── Entidades ─────────────────────────────────────────────────────────────
    private readonly Player       _player;
    private readonly List<Enemy>  _enemies = new();
    private readonly List<Item>   _items   = new();

    // ── Estado do jogo ────────────────────────────────────────────────────────
    private GameState _state      = GameState.Playing;
    private string    _message    = "";
    private int       _msgTimer;
    private int       _tick;
    private int       _camX, _camY;
    private int       _killCount;
    private int       _footstepTimer;

    public GameEngine(Database? db = null, UserAccount? user = null)
    {
        _db     = db;
        _user   = user;
        _player = new Player(5, 5);
        SpawnEnemies(14);
        SpawnItems();
    }

    private void SpawnEnemies(int count)
    {
        var rng = new Random(7);
        for (int i = 0; i < count; i++)
        {
            int x, y;
            do { x = rng.Next(10, WorldW - 5); y = rng.Next(5, WorldH - 5); }
            while (!_world.IsWalkable(x, y) ||
                   Math.Abs(x - _player.X) + Math.Abs(y - _player.Y) < 14);
            _enemies.Add(new Enemy(x, y));
        }
    }

    private void SpawnItems()
    {
        var rng = new Random(31);
        var types = new[] {
            ItemType.RedDrink, ItemType.RedDrink, ItemType.RedDrink, ItemType.RedDrink,
            ItemType.AmmoPack, ItemType.AmmoPack, ItemType.AmmoPack, ItemType.AmmoPack,
            ItemType.BlueDrink, ItemType.BlueDrink, ItemType.BlueDrink
        };
        foreach (var t in types)
        {
            int x, y;
            do { x = rng.Next(2, WorldW - 2); y = rng.Next(2, WorldH - 2); }
            while (!_world.IsWalkable(x, y) || _items.Any(i => i.X == x && i.Y == y));
            _items.Add(new Item(x, y, t));
        }
    }

    // ── Loop principal ───────────────────────────────────────────────────────
    public void Run()
    {
        Console.CursorVisible = false;
        try { Console.SetWindowSize(56, 32); Console.SetBufferSize(56, 32); } catch { }
        Console.Clear();
        SetMsg("Bem-vindo, soldado! Elimina todos os inimigos.");

        while (_state == GameState.Playing)
        {
            _input.Poll();
            UpdateCamera();
            ProcessInput();
            Update();
            _renderer.Render(
                _world, _player, _enemies, _items, _combat.Bullets,
                _camX, _camY,
                _input.MouseCellX, _input.MouseCellY,
                _input.RightHeld,
                _message, _killCount, _enemies.Count);
            Thread.Sleep(70); // ~14 FPS
        }

        ShowEndScreen();
    }

    private void UpdateCamera()
    {
        _camX = Math.Clamp(_player.X - ViewW / 2, 0, WorldW - ViewW);
        _camY = Math.Clamp(_player.Y - ViewH / 2, 0, WorldH - ViewH);
    }

    // ── Input ─────────────────────────────────────────────────────────────────
    private void ProcessInput()
    {
        // Direção de mira baseada no rato (8 direções)
        int mouseWorldX = _input.MouseCellX + _camX;
        int mouseWorldY = _input.MouseCellY + _camY;
        int aimDx = Math.Sign(mouseWorldX - _player.X);
        int aimDy = Math.Sign(mouseWorldY - _player.Y);
        if (aimDx == 0 && aimDy == 0) aimDx = 1;
        _player.AimDx = aimDx;
        _player.AimDy = aimDy;

        // Movimento WASD
        bool moved = false;
        if (_input.IsHeld(ConsoleKey.W)) { TryMove( 0, -1, Direction.Up);    moved = true; }
        if (_input.IsHeld(ConsoleKey.S)) { TryMove( 0,  1, Direction.Down);  moved = true; }
        if (_input.IsHeld(ConsoleKey.A)) { TryMove(-1,  0, Direction.Left);  moved = true; }
        if (_input.IsHeld(ConsoleKey.D)) { TryMove( 1,  0, Direction.Right); moved = true; }

        // Som de passos
        if (moved && !_player.IsRolling)
        {
            _footstepTimer++;
            if (_footstepTimer >= 5) { _sound.PlayFootstep(); _footstepTimer = 0; }
        }

        // Roll (SPACE)
        if (_input.IsPressed(ConsoleKey.Spacebar) && _player.CanRoll)
        {
            _player.StartRoll();
            SetMsg("ROLL! [invencivel]");
        }

        // Atirar (botão ESQUERDO do rato - contínuo enquanto premido)
        if (_input.LeftHeld)
        {
            if (_player.Weapon.IsEmpty)
            {
                // Magazine vazia: som de clique e auto-recarga
                if (_input.LeftPressed) { _sound.PlayEmpty(); _player.Weapon.StartReload(); }
            }
            else if (_combat.PlayerShoot(_player))
            {
                _sound.PlayGunshot();
            }
        }

        // Recarregar (R)
        if (_input.IsPressed(ConsoleKey.R))
        {
            if (_player.Weapon.StartReload()) _sound.PlayReload();
        }

        // Soco (G)
        if (_input.IsPressed(ConsoleKey.G))
        {
            bool hit = _combat.PlayerPunch(_player, _enemies);
            SetMsg(hit ? "POW! Soco certeiro!" : "Soco no ar...");
        }

        // Pegar item (E)
        if (_input.IsPressed(ConsoleKey.E))
            TryPickupNearby();

        // Sair
        if (_input.IsPressed(ConsoleKey.Escape))
            _state = GameState.GameOver;
    }

    private void TryMove(int dx, int dy, Direction dir)
    {
        if (_player.IsRolling) return;
        _player.MovingDir = dir;
        int nx = _player.X + dx;
        int ny = _player.Y + dy;
        if (_world.IsWalkable(nx, ny) && !EnemyAt(nx, ny))
        { _player.X = nx; _player.Y = ny; }
    }

    private void TryPickupNearby()
    {
        // Procura item adjacente ou na mesma posição
        for (int dy = -1; dy <= 1; dy++)
        for (int dx = -1; dx <= 1; dx++)
        {
            int wx = _player.X + dx, wy = _player.Y + dy;
            var item = _items.FirstOrDefault(i => i.IsActive && i.X == wx && i.Y == wy);
            if (item == null) continue;
            ApplyItem(item);
            return;
        }
        SetMsg("Nada para pegar aqui...");
    }

    private void ApplyItem(Item item)
    {
        item.IsActive = false;
        switch (item.Type)
        {
            case ItemType.RedDrink:
                _player.DrinkRed();
                _sound.PlayPickup();
                SetMsg("Bebida Vermelha! HP MAXIMO 200 - FULL HEAL!");
                break;
            case ItemType.BlueDrink:
                _player.DrinkBlue();
                _sound.PlayPoison();
                SetMsg("Bebida Azul! VENENO! -40 HP!");
                break;
            case ItemType.AmmoPack:
                _player.Weapon.AddAmmo(30);
                _sound.PlayPickup();
                SetMsg("Mochila de municao! +30 balas de reserva.");
                break;
        }
    }

    // ── Update ────────────────────────────────────────────────────────────────
    private void Update()
    {
        _tick++;
        _player.Update();

        // Movimento durante roll
        if (_player.IsRolling)
        {
            var (rdx, rdy) = _player.RollDir.ToVector();
            int rx = _player.X + rdx, ry = _player.Y + rdy;
            if (_world.IsWalkable(rx, ry)) { _player.X = rx; _player.Y = ry; }
        }

        // Inimigos: mover + atirar
        int prevKills = _killCount;
        foreach (var e in _enemies)
        {
            if (!e.IsAlive) { continue; }
            e.Update();

            if (e.CanMove)
            {
                var (mdx, mdy) = e.GetMoveDirection(_player.X, _player.Y);
                int nx = e.X + mdx, ny = e.Y + mdy;
                if (_world.IsWalkable(nx, ny) && !EnemyAt(nx, ny) &&
                    !(nx == _player.X && ny == _player.Y))
                { e.X = nx; e.Y = ny; }
                e.UseMoveCooldown();
            }
        }

        // Contar mortes
        _killCount = _enemies.Count(e => !e.IsAlive);
        if (_killCount > prevKills)
        {
            int diff = _killCount - prevKills;
            SetMsg($"INIMIGO ELIMINADO! ({_killCount}/{_enemies.Count})");
        }

        _combat.EnemiesShoot(_enemies, _player);
        _combat.UpdateBullets(_world, _player, _enemies);

        bool wasAlive = _player.IsAlive;
        _combat.EnemyMeleeAttacks(_enemies, _player);
        if (wasAlive && _player.Health < _player.MaxHealth / 2 && _tick % 30 == 0)
            SetMsg("HP CRITICO! Procura a bebida vermelha [r]!");

        if (!wasAlive || !_player.IsAlive)
        {
            if (!_player.IsAlive) { _sound.PlayDeath(); _state = GameState.GameOver; }
        }

        // Pickup automático ao pisar
        foreach (var item in _items)
            if (item.IsActive && item.X == _player.X && item.Y == _player.Y)
                ApplyItem(item);

        // Vitória
        if (_enemies.All(e => !e.IsAlive)) _state = GameState.Win;

        // Timer mensagem
        if (_msgTimer > 0 && --_msgTimer == 0) _message = "";
    }

    private bool EnemyAt(int x, int y) => _enemies.Any(e => e.IsAlive && e.X == x && e.Y == y);

    private void SetMsg(string msg, int ticks = 20) { _message = msg; _msgTimer = ticks; }

    // ── Ecrã final ───────────────────────────────────────────────────────────
    private void ShowEndScreen()
    {
        Console.Clear();
        if (_state == GameState.Win)
        {
            Console.ForegroundColor = ConsoleColor.Yellow;
            Console.WriteLine("╔══════════════════════════════════╗");
            Console.WriteLine("║  VITORIA! Todos eliminados!      ║");
            Console.WriteLine("╚══════════════════════════════════╝");
        }
        else
        {
            Console.ForegroundColor = ConsoleColor.Red;
            Console.WriteLine("╔══════════════════════════════════╗");
            Console.WriteLine("║       GAME OVER - Morreste!      ║");
            Console.WriteLine("╚══════════════════════════════════╝");
        }
        Console.ResetColor();

        int score = _killCount * 100 + _player.Health;

        Console.WriteLine($"\n  Kills     : {_killCount}/{_enemies.Count}");
        Console.WriteLine($"  HP final  : {_player.Health}/{_player.MaxHealth}");
        Console.WriteLine($"  Score     : {score} pts");
        Console.WriteLine($"  Municao   : {_player.Weapon.MagazineAmmo} | {_player.Weapon.ReserveAmmo}");

        // Guarda na base de dados se o jogador fez login
        if (_db != null && _user != null)
        {
            _db.SaveGameResult(_user.Id, _killCount, score);

            bool newRecord = score > _user.HighScore;
            if (newRecord)
            {
                Console.ForegroundColor = ConsoleColor.Yellow;
                Console.WriteLine($"\n  *** NOVO RECORDE! {score} pts (anterior: {_user.HighScore}) ***");
                Console.ResetColor();
            }
            else
            {
                Console.ForegroundColor = ConsoleColor.DarkGray;
                Console.WriteLine($"\n  HighScore: {_user.HighScore} pts");
                Console.ResetColor();
            }
        }

        Console.WriteLine("\n  Prima qualquer tecla para sair...");
        Console.ReadKey(true);
    }

    public void Dispose() => _input.Dispose();
}
