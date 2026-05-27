using System.Runtime.InteropServices;

namespace ShooterGame;

// Captura teclado + rato (botões + posição do cursor no ecrã)
public sealed class ConsoleInput : IDisposable
{
    // ── Windows API ──────────────────────────────────────────────────────────

    [DllImport("user32.dll")]
    private static extern short GetAsyncKeyState(int vKey);

    [DllImport("user32.dll")]
    private static extern bool GetCursorPos(out POINT lpPoint);

    [DllImport("user32.dll")]
    private static extern bool ScreenToClient(IntPtr hWnd, ref POINT lpPoint);

    [DllImport("kernel32.dll")]
    private static extern IntPtr GetConsoleWindow();

    [DllImport("kernel32.dll", SetLastError = true)]
    private static extern IntPtr GetStdHandle(int nStdHandle);

    [DllImport("kernel32.dll", SetLastError = true)]
    private static extern bool GetCurrentConsoleFontEx(IntPtr hOut, bool bMax, ref CONSOLE_FONT_INFOEX info);

    [DllImport("kernel32.dll", SetLastError = true)]
    private static extern bool SetConsoleMode(IntPtr h, uint mode);

    [DllImport("kernel32.dll", SetLastError = true)]
    private static extern bool GetConsoleMode(IntPtr h, out uint mode);

    [StructLayout(LayoutKind.Sequential)] private struct POINT { public int X, Y; }

    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    private struct CONSOLE_FONT_INFOEX
    {
        public uint cbSize;
        public uint nFont;
        public COORD dwFontSize;
        [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 32)] public string FaceName;
        public ushort FontFamily, FontWeight;
    }

    [StructLayout(LayoutKind.Sequential)] private struct COORD { public short X, Y; }

    private const int  VK_LBUTTON = 0x01;
    private const int  VK_RBUTTON = 0x02;
    private const uint ENABLE_QUICK_EDIT = 0x0040;

    // ── Estado ───────────────────────────────────────────────────────────────

    // Posição do rato em células da consola
    public int  MouseCellX    { get; private set; }
    public int  MouseCellY    { get; private set; }

    // Botões do rato
    public bool LeftHeld      { get; private set; }   // LMB (atirar)
    public bool LeftPressed   { get; private set; }   // acabou de carregar (LMB)
    public bool RightHeld     { get; private set; }   // RMB (ADS)
    public bool RightPressed  { get; private set; }   // acabou de carregar (RMB)

    // Teclas (por frame e contínuo)
    private readonly HashSet<ConsoleKey> _held    = new();
    private readonly HashSet<ConsoleKey> _pressed = new();

    private bool _prevLeft;
    private bool _prevRight;

    private readonly IntPtr _hWnd;
    private readonly IntPtr _hOut;
    private int _cellW = 8, _cellH = 16;
    private readonly uint _origMode;

    public ConsoleInput()
    {
        _hWnd = GetConsoleWindow();
        _hOut = GetStdHandle(-11);

        // Desactiva Quick-Edit para o rato não selecionar texto
        if (GetConsoleMode(GetStdHandle(-10), out _origMode))
            SetConsoleMode(GetStdHandle(-10), _origMode & ~ENABLE_QUICK_EDIT);

        RefreshFontSize();
    }

    private void RefreshFontSize()
    {
        var fi = new CONSOLE_FONT_INFOEX { cbSize = (uint)Marshal.SizeOf<CONSOLE_FONT_INFOEX>() };
        if (GetCurrentConsoleFontEx(_hOut, false, ref fi) && fi.dwFontSize.X > 0)
        { _cellW = fi.dwFontSize.X; _cellH = fi.dwFontSize.Y; }
    }

    public void Poll()
    {
        _pressed.Clear();
        _prevLeft    = LeftHeld;
        _prevRight   = RightHeld;
        LeftPressed  = false;
        RightPressed = false;

        // ── Teclado ──
        // Varre as teclas mais usadas no jogo sem bloquear
        ConsoleKey[] watch = {
            ConsoleKey.W, ConsoleKey.A, ConsoleKey.S, ConsoleKey.D,
            ConsoleKey.E, ConsoleKey.R, ConsoleKey.G, ConsoleKey.Spacebar,
            ConsoleKey.Escape
        };
        foreach (var k in watch)
        {
            bool down = (GetAsyncKeyState((int)k) & 0x8000) != 0;
            if (down  && !_held.Contains(k)) { _pressed.Add(k); _held.Add(k); }
            if (!down && _held.Contains(k))  { _held.Remove(k); }
        }

        // Lê teclas restantes do buffer de Console sem bloquear
        while (Console.KeyAvailable)
        {
            var k = Console.ReadKey(true).Key;
            if (!_held.Contains(k)) { _pressed.Add(k); _held.Add(k); }
        }

        // ── Rato – botões ──
        LeftHeld  = (GetAsyncKeyState(VK_LBUTTON) & 0x8000) != 0;
        RightHeld = (GetAsyncKeyState(VK_RBUTTON) & 0x8000) != 0;
        if (LeftHeld  && !_prevLeft)  LeftPressed  = true;
        if (RightHeld && !_prevRight) RightPressed = true;

        // ── Rato – posição ──
        if (GetCursorPos(out var pt))
        {
            ScreenToClient(_hWnd, ref pt);
            MouseCellX = Math.Max(0, pt.X / Math.Max(1, _cellW));
            MouseCellY = Math.Max(0, pt.Y / Math.Max(1, _cellH));
        }
    }

    public bool IsHeld(ConsoleKey k)    => _held.Contains(k);
    public bool IsPressed(ConsoleKey k) => _pressed.Contains(k);

    public void Dispose()
    {
        SetConsoleMode(GetStdHandle(-10), _origMode);
    }
}
