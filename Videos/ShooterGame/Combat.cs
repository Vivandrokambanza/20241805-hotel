namespace ShooterGame;

public class Combat
{
    private const int BulletDamage     = 34;   // 3 tiros para matar inimigo (100 HP)
    private const int PunchDamage      = 50;   // 2 socos para matar
    private const int EnemyMeleeDmg   = 20;
    private const int EnemyBulletDmg  = 15;

    public List<Bullet> Bullets { get; } = new();

    // --- Tiro do jogador (botão direito do rato) ---
    public bool PlayerShoot(Player player)
    {
        if (!player.Weapon.TryFire()) return false;
        int sx = player.X + player.AimDx;
        int sy = player.Y + player.AimDy;
        Bullets.Add(new Bullet(sx, sy, player.AimDx, player.AimDy, fromPlayer: true));
        return true;
    }

    // --- Soco do jogador (tecla G) ---
    public bool PlayerPunch(Player player, List<Enemy> enemies)
    {
        if (!player.CanPunch) return false;
        int tx = player.X + player.AimDx;
        int ty = player.Y + player.AimDy;
        bool hit = false;
        foreach (var e in enemies)
            if (e.IsAlive && e.X == tx && e.Y == ty) { e.TakeDamage(PunchDamage); hit = true; }
        player.UsePunchCooldown();
        return hit;
    }

    // --- Tiro dos inimigos ---
    public void EnemiesShoot(List<Enemy> enemies, Player player)
    {
        foreach (var e in enemies)
        {
            if (!e.IsAlive || !e.CanShoot) continue;
            if (!e.InShootRange(player.X, player.Y)) continue;
            var (dx, dy) = e.GetAimDirection(player.X, player.Y);
            Bullets.Add(new Bullet(e.X + dx, e.Y + dy, dx, dy, fromPlayer: false));
            e.UseShootCooldown();
        }
    }

    // --- Atualiza todas as balas ---
    public void UpdateBullets(World world, Player player, List<Enemy> enemies)
    {
        for (int i = Bullets.Count - 1; i >= 0; i--)
        {
            var b  = Bullets[i];
            int nx = b.X + b.Dx;
            int ny = b.Y + b.Dy;

            if (!world.IsWalkable(nx, ny)) { Bullets.RemoveAt(i); continue; }

            b.X = nx; b.Y = ny;

            if (b.FromPlayer)
            {
                bool hit = false;
                foreach (var e in enemies)
                    if (e.IsAlive && e.X == nx && e.Y == ny) { e.TakeDamage(BulletDamage); hit = true; }
                if (hit) { Bullets.RemoveAt(i); continue; }
            }
            else
            {
                if (nx == player.X && ny == player.Y)
                {
                    player.TakeDamage(EnemyBulletDmg);
                    Bullets.RemoveAt(i);
                }
            }
        }
    }

    // --- Ataque corpo-a-corpo dos inimigos ---
    public void EnemyMeleeAttacks(List<Enemy> enemies, Player player)
    {
        foreach (var e in enemies)
        {
            if (!e.IsAlive || !e.CanAttack) continue;
            int dist = Math.Abs(e.X - player.X) + Math.Abs(e.Y - player.Y);
            if (dist <= 1) { player.TakeDamage(EnemyMeleeDmg); e.UseAttackCooldown(); }
        }
    }
}
