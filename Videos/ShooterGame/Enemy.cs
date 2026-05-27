namespace ShooterGame;

public class Enemy
{
    public int  X         { get; set; }
    public int  Y         { get; set; }
    public int  Health    { get; private set; }
    public int  MaxHealth { get; }
    public bool IsAlive   => Health > 0;

    private int _moveCooldown;
    private int _attackCooldown;
    private int _shootCooldown;

    private const int MoveInterval   = 8;
    private const int AttackInterval = 20;
    private const int ShootInterval  = 35;  // tiros mais lentos que o jogador
    private const int ShootRange     = 14;  // distância máxima para atirar

    public bool CanMove   => _moveCooldown   == 0;
    public bool CanAttack => _attackCooldown == 0;
    public bool CanShoot  => _shootCooldown  == 0;

    public Enemy(int x, int y, int health = 100)
    {
        X = x; Y = y;
        Health = health; MaxHealth = health;
        _moveCooldown = Random.Shared.Next(0, MoveInterval);
    }

    public void Update()
    {
        if (_moveCooldown   > 0) _moveCooldown--;
        if (_attackCooldown > 0) _attackCooldown--;
        if (_shootCooldown  > 0) _shootCooldown--;
    }

    public void TakeDamage(int amount) => Health = Math.Max(0, Health - amount);

    public void UseMoveCooldown()   => _moveCooldown   = MoveInterval;
    public void UseAttackCooldown() => _attackCooldown = AttackInterval;
    public void UseShootCooldown()  => _shootCooldown  = ShootInterval;

    public bool InShootRange(int px, int py) =>
        Math.Abs(X - px) + Math.Abs(Y - py) <= ShootRange;

    public (int dx, int dy) GetMoveDirection(int px, int py)
    {
        int dx = Math.Sign(px - X);
        int dy = Math.Sign(py - Y);
        int ax = Math.Abs(px - X);
        int ay = Math.Abs(py - Y);
        if (ax > ay) return (dx, 0);
        if (ay > ax) return (0, dy);
        return Random.Shared.Next(2) == 0 ? (dx, 0) : (0, dy);
    }

    public (int dx, int dy) GetAimDirection(int px, int py) =>
        (Math.Sign(px - X), Math.Sign(py - Y));

    // Símbolo visual baseado em HP
    public char Symbol => (double)Health / MaxHealth > 0.5 ? 'E' : 'e';
}
