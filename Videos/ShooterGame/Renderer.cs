namespace ShooterGame;

public class Renderer
{
    public const int ViewW = 52;
    public const int ViewH = 22;

    private readonly char[] _lineBuf = new char[ViewW];

    public void Render(
        World world, Player player,
        List<Enemy> enemies, List<Item> items, List<Bullet> bullets,
        int camX, int camY,
        int crossX, int crossY,   // posição da mira em células da consola
        bool isADS, string message, int killCount, int totalEnemies)
    {
        Console.SetCursorPosition(0, 0);
        var sb = new System.Text.StringBuilder((ViewW + 1) * (ViewH + 8));

        // ── Mapa ────────────────────────────────────────────────────────────
        for (int row = 0; row < ViewH; row++)
        {
            int worldY = camY + row;
            for (int col = 0; col < ViewW; col++)
            {
                int worldX = camX + col;
                _lineBuf[col] = GetChar(world, worldX, worldY,
                                        player, enemies, items, bullets,
                                        crossX, crossY, col, row, isADS);
            }
            sb.Append(_lineBuf).Append('\n');
        }

        // ── HUD ─────────────────────────────────────────────────────────────
        sb.Append(new string('=', ViewW)).Append('\n');

        // Linha 1: HP
        sb.Append(BuildHPLine(player));
        sb.Append("   KILLS: ").Append(killCount).Append('/').Append(totalEnemies).Append('\n');

        // Linha 2: Arma + munição
        sb.Append(BuildAmmoLine(player.Weapon));
        sb.Append('\n');

        // Linha 3: Controlos rápidos
        sb.Append("WASD:Mover  SPACE:Roll  LMB:Atirar  RMB:ADS  E:Pegar  R:Recarregar\n");

        // Linha 4: Mensagem
        sb.Append(message.PadRight(ViewW + 2));

        Console.Write(sb);
    }

    private char GetChar(
        World world, int wx, int wy,
        Player player, List<Enemy> enemies, List<Item> items, List<Bullet> bullets,
        int crossScreenX, int crossScreenY, int col, int row, bool isADS)
    {
        // Mira do rato
        if (col == crossScreenX && row == crossScreenY)
            return isADS ? '+' : '+';

        // Jogador
        if (wx == player.X && wy == player.Y)
            return player.IsRolling ? 'O' : '@';

        // Inimigos
        foreach (var e in enemies)
            if (e.IsAlive && e.X == wx && e.Y == wy) return e.Symbol;

        // Balas
        foreach (var b in bullets)
            if (b.X == wx && b.Y == wy) return b.FromPlayer ? '*' : '.';

        // Itens (apenas visíveis perto — simulação de campo de visão)
        foreach (var i in items)
            if (i.IsActive && i.X == wx && i.Y == wy) return i.Symbol;

        if (!world.InBounds(wx, wy)) return ' ';
        return world.Tiles[wx, wy] == TileType.Wall ? '#' : ' ';
    }

    private static string BuildHPLine(Player p)
    {
        int barLen = 20;
        int filled = (int)Math.Round((double)p.Health / p.MaxHealth * barLen);
        filled = Math.Clamp(filled, 0, barLen);
        string bar   = new string('|', filled) + new string('-', barLen - filled);
        string status = p.Health > p.MaxHealth * 0.5 ? "OK"
                      : p.Health > p.MaxHealth * 0.25 ? "FERIDO!"
                      : "CRITICO!!!";
        return $"HP [{bar}] {p.Health,3}/{p.MaxHealth}  {status}";
    }

    private static string BuildAmmoLine(Weapon w)
    {
        if (w.IsReloading)
        {
            int pct  = w.ReloadPercent;
            int bars = pct / 5; // 0-20
            string bar = new string('>', bars) + new string('.', 20 - bars);
            return $"{w.Name}  RECARREGANDO [{bar}] {pct,3}%";
        }
        string ammoColor = w.MagazineAmmo <= 5 ? "!!!" : "   ";
        return $"{w.Name}  {w.MagazineAmmo,2}/{w.MagazineSize} | {w.ReserveAmmo,-3}{ammoColor}";
    }
}
