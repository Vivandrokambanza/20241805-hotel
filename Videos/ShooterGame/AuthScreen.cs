namespace ShooterGame;

public class AuthScreen
{
    private readonly Database _db;
    private const int W = 48; // largura interior da caixa

    public AuthScreen(Database db) => _db = db;

    // Devolve a conta autenticada (null = sair)
    public UserAccount? Show()
    {
        while (true)
        {
            Console.Clear();
            Console.CursorVisible = false;

            DrawBanner();
            Console.ForegroundColor = ConsoleColor.Cyan;
            BoxLine("  [1]  Iniciar Sessao");
            BoxLine("  [2]  Criar Conta   (Registar)");
            BoxLine("  [3]  Sair");
            BoxLine("");
            BoxBottom();
            Console.ResetColor();

            var key = Console.ReadKey(true).Key;
            switch (key)
            {
                case ConsoleKey.D1 or ConsoleKey.NumPad1:
                    var user = DoLogin();
                    if (user != null) return user;
                    break;
                case ConsoleKey.D2 or ConsoleKey.NumPad2:
                    DoRegister();
                    break;
                case ConsoleKey.D3 or ConsoleKey.NumPad3 or ConsoleKey.Escape:
                    return null;
            }
        }
    }

    // ── Iniciar Sessão ────────────────────────────────────────────────────────
    private UserAccount? DoLogin()
    {
        while (true)
        {
            Console.Clear();
            Console.CursorVisible = true;
            SectionTitle("INICIAR SESSAO");

            Console.Write("  Username  : ");
            string username = Console.ReadLine() ?? "";

            Console.Write("  Password  : ");
            string password = ReadPassword();

            Console.CursorVisible = false;
            Console.WriteLine();

            var (user, error) = _db.Login(username, password);

            if (user != null)
            {
                Console.ForegroundColor = ConsoleColor.Green;
                Console.WriteLine($"  Bem-vindo, {user.Username}!");
                Console.WriteLine($"  HighScore : {user.HighScore,6} pts");
                Console.WriteLine($"  Kills     : {user.TotalKills,6}  |  Jogos: {user.TotalGames}");
                Console.WriteLine($"  Membro desde: {user.CreatedAt}");
                Console.ResetColor();
                Console.WriteLine("\n  Prima qualquer tecla para entrar no jogo...");
                Console.ReadKey(true);
                return user;
            }

            PrintError(error);
            if (!AskRetry()) return null;
        }
    }

    // ── Criar Conta ───────────────────────────────────────────────────────────
    private void DoRegister()
    {
        while (true)
        {
            Console.Clear();
            Console.CursorVisible = true;
            SectionTitle("CRIAR NOVA CONTA");

            Console.Write("  Username         : ");
            string username = Console.ReadLine() ?? "";

            Console.Write("  Password         : ");
            string password = ReadPassword();

            Console.Write("  Confirmar pass   : ");
            string confirm  = ReadPassword();

            Console.CursorVisible = false;
            Console.WriteLine();

            if (password != confirm)
            {
                PrintError("As passwords nao coincidem.");
                if (!AskRetry()) return;
                continue;
            }

            var (ok, error) = _db.Register(username, password);

            if (ok)
            {
                Console.ForegroundColor = ConsoleColor.Green;
                Console.WriteLine($"  Conta '{username}' criada com sucesso!");
                Console.WriteLine("  Ja podes fazer login.");
                Console.ResetColor();
                Console.WriteLine("\n  Prima qualquer tecla...");
                Console.ReadKey(true);
                return;
            }

            PrintError(error);
            if (!AskRetry()) return;
        }
    }

    // ── UI Helpers ────────────────────────────────────────────────────────────

    private static void DrawBanner()
    {
        Console.ForegroundColor = ConsoleColor.Yellow;
        string top  = new string('═', W);
        string sep  = new string('═', W);
        Console.WriteLine($"  ╔{top}╗");
        CenteredLine("CALL OF DUTY: CONSOLA WARS  v1.0");
        CenteredLine("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        Console.WriteLine($"  ╠{sep}╣");
        BoxLine("");
    }

    private static void BoxBottom()
    {
        Console.ForegroundColor = ConsoleColor.Yellow;
        Console.WriteLine($"  ╚{new string('═', W)}╝");
        Console.ResetColor();
    }

    private static void BoxLine(string text)
    {
        // Preenche até à largura da caixa
        string padded = text.Length <= W - 2
            ? text.PadRight(W - 2)
            : text[..(W - 2)];
        Console.ForegroundColor = ConsoleColor.Cyan;
        Console.ForegroundColor = ConsoleColor.Yellow;
        Console.Write("  ║");
        Console.ForegroundColor = ConsoleColor.Cyan;
        Console.Write(padded);
        Console.ForegroundColor = ConsoleColor.Yellow;
        Console.WriteLine("║");
    }

    private static void CenteredLine(string text)
    {
        int pad   = (W - 2 - text.Length) / 2;
        int rpad  = W - 2 - text.Length - pad;
        string line = new string(' ', pad) + text + new string(' ', rpad);
        Console.Write("  ║");
        Console.ForegroundColor = ConsoleColor.White;
        Console.Write(line);
        Console.ForegroundColor = ConsoleColor.Yellow;
        Console.WriteLine("║");
    }

    private static void SectionTitle(string title)
    {
        Console.ForegroundColor = ConsoleColor.Yellow;
        int pad   = (W - title.Length - 2) / 2;
        int rpad  = W - title.Length - 2 - pad;
        string bar = new string('═', pad) + $" {title} " + new string('═', rpad);
        Console.WriteLine($"  ╔{bar}╗");
        Console.ResetColor();
        Console.WriteLine();
    }

    private static void PrintError(string msg)
    {
        Console.ForegroundColor = ConsoleColor.Red;
        Console.WriteLine($"  [!] {msg}");
        Console.ResetColor();
    }

    private static bool AskRetry()
    {
        Console.ForegroundColor = ConsoleColor.DarkGray;
        Console.WriteLine("  [R] Tentar novamente   [ESC] Voltar ao menu");
        Console.ResetColor();
        return Console.ReadKey(true).Key != ConsoleKey.Escape;
    }

    // Lê password escondendo os caracteres com '*'
    private static string ReadPassword()
    {
        var sb = new System.Text.StringBuilder();
        while (true)
        {
            var k = Console.ReadKey(true);
            if (k.Key == ConsoleKey.Enter)   { Console.WriteLine(); break; }
            if (k.Key == ConsoleKey.Escape)  { Console.WriteLine(); return ""; }
            if (k.Key == ConsoleKey.Backspace)
            {
                if (sb.Length > 0) { sb.Remove(sb.Length - 1, 1); Console.Write("\b \b"); }
            }
            else if (k.KeyChar != '\0')
            {
                sb.Append(k.KeyChar);
                Console.Write('*');
            }
        }
        return sb.ToString();
    }
}
