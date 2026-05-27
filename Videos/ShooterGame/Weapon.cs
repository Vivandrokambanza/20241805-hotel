namespace ShooterGame;

public class Weapon
{
    public string Name         { get; }
    public int    MagazineSize { get; }
    public int    MagazineAmmo { get; private set; }
    public int    ReserveAmmo  { get; private set; }
    public bool   IsReloading  { get; private set; }

    private int _fireCooldown;
    private int _reloadTimer;

    private const int FireRate       = 3;   // ticks entre disparos
    private const int ReloadDuration = 25;  // ticks para recarregar (~2s)

    public bool CanFire      => !IsReloading && MagazineAmmo > 0 && _fireCooldown == 0;
    public bool IsEmpty      => MagazineAmmo == 0;
    public bool HasAnyAmmo   => MagazineAmmo > 0 || ReserveAmmo > 0;
    public int  ReloadPercent => IsReloading ? (int)(100.0 * (ReloadDuration - _reloadTimer) / ReloadDuration) : 100;

    public Weapon(string name, int magazineSize, int reserveAmmo)
    {
        Name         = name;
        MagazineSize = magazineSize;
        MagazineAmmo = magazineSize;
        ReserveAmmo  = reserveAmmo;
    }

    public bool TryFire()
    {
        if (!CanFire) return false;
        MagazineAmmo--;
        _fireCooldown = FireRate;
        return true;
    }

    public bool StartReload()
    {
        if (IsReloading) return false;
        if (MagazineAmmo == MagazineSize) return false;
        if (ReserveAmmo  == 0) return false;
        IsReloading  = true;
        _reloadTimer = ReloadDuration;
        return true;
    }

    public void Update()
    {
        if (_fireCooldown > 0) _fireCooldown--;

        if (IsReloading)
        {
            _reloadTimer--;
            if (_reloadTimer <= 0)
            {
                int needed   = MagazineSize - MagazineAmmo;
                int refill   = Math.Min(needed, ReserveAmmo);
                MagazineAmmo += refill;
                ReserveAmmo  -= refill;
                IsReloading   = false;
            }
        }
    }

    public void AddAmmo(int amount) => ReserveAmmo += amount;
}
