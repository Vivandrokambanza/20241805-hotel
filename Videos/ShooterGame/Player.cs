namespace ShooterGame;

public class Player
{
    public int     X          { get; set; }
    public int     Y          { get; set; }
    public int     Health     { get; private set; }
    public int     MaxHealth  { get; private set; }
    public Weapon  Weapon     { get; }

    // Direção de mira (8 direções via rato, -1/0/1 em cada eixo)
    public int AimDx { get; set; } = 1;
    public int AimDy { get; set; } = 0;

    // Direção de movimento (WASD, usada para o roll)
    public Direction MovingDir { get; set; } = Direction.Right;

    // --- Roll (estilo GTA) ---
    public bool      IsRolling    { get; private set; }
    public Direction RollDir      { get; private set; }
    private int _rollFrames;
    private int _rollCooldown;
    private const int RollDuration       = 5;
    private const int RollCooldownFrames = 12;

    // --- Soco ---
    private int _punchCooldown;
    private const int PunchCooldown = 10;

    public bool CanRoll  => !IsRolling && _rollCooldown == 0;
    public bool CanPunch => _punchCooldown == 0;
    public bool IsInvincible => IsRolling;
    public bool IsAlive      => Health > 0;

    public Player(int x, int y)
    {
        X = x; Y = y;
        Health    = 100;
        MaxHealth = 100;
        Weapon    = new Weapon("M4A1", 30, 90);
    }

    public void Update()
    {
        Weapon.Update();

        if (IsRolling)
        {
            _rollFrames--;
            if (_rollFrames <= 0) { IsRolling = false; _rollCooldown = RollCooldownFrames; }
        }
        if (_rollCooldown  > 0) _rollCooldown--;
        if (_punchCooldown > 0) _punchCooldown--;
    }

    public void StartRoll()
    {
        if (!CanRoll) return;
        IsRolling   = true;
        RollDir     = MovingDir;
        _rollFrames = RollDuration;
    }

    public void TakeDamage(int amount)
    {
        if (IsInvincible) return;
        Health = Math.Max(0, Health - amount);
    }

    public void DrinkRed()  { MaxHealth = 200; Health = 200; }
    public void DrinkBlue() { Health = Math.Max(0, Health - 40); }

    public void UsePunchCooldown() => _punchCooldown = PunchCooldown;
}
