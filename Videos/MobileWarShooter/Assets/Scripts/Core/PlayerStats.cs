using UnityEngine;

namespace WarZone
{
    // Centraliza todas as estatísticas do jogador — modificadas por upgrades e equipamentos
    public class PlayerStats : MonoBehaviour
    {
        public static PlayerStats Instance { get; private set; }

        // ── Base stats (modificadas em runtime por upgrades) ──────────────────
        public float DamageMultiplier  { get; private set; } = 1f;
        public float FireRateMultiplier{ get; private set; } = 1f;
        public float SpeedMultiplier   { get; private set; } = 1f;
        public float BulletRange       { get; private set; } = 8f;
        public int   MaxHpBonus        { get; private set; } = 0;

        // ── Habilidades especiais (desbloqueadas por upgrades) ────────────────
        public bool HasPiercingBullets { get; private set; }   // bala atravessa inimigos
        public bool HasDoubleShotgun   { get; private set; }   // 2 balas por disparo
        public bool HasExplosiveBullets{ get; private set; }   // bala explode
        public bool HasRicochetBullets { get; private set; }   // bala ricocheteía
        public int  ShieldCharges      { get; private set; }   // absorve N golpes

        // Contadores de upgrade para evitar stack infinito
        private int _damageUpgrades, _fireRateUpgrades, _speedUpgrades;
        private const int MaxStackPerType = 5;

        public event System.Action OnStatsChanged;

        void Awake()
        {
            if (Instance != null) { Destroy(gameObject); return; }
            Instance = this;
        }

        // ── Aplicar upgrades ─────────────────────────────────────────────────
        public void ApplyUpgrade(UpgradeType type)
        {
            switch (type)
            {
                case UpgradeType.Damage:
                    if (_damageUpgrades >= MaxStackPerType) break;
                    DamageMultiplier += 0.20f;
                    _damageUpgrades++;
                    break;

                case UpgradeType.FireRate:
                    if (_fireRateUpgrades >= MaxStackPerType) break;
                    FireRateMultiplier += 0.15f;
                    _fireRateUpgrades++;
                    break;

                case UpgradeType.Speed:
                    if (_speedUpgrades >= MaxStackPerType) break;
                    SpeedMultiplier += 0.15f;
                    _speedUpgrades++;
                    break;

                case UpgradeType.MaxHP:
                    MaxHpBonus += 30;
                    PlayerHealth.Instance?.UpgradeMaxHp(30);
                    break;

                case UpgradeType.HealNow:
                    PlayerHealth.Instance?.Heal(40);
                    break;

                case UpgradeType.BulletRange:
                    BulletRange += 2f;
                    break;

                case UpgradeType.PiercingBullets:
                    HasPiercingBullets = true;
                    break;

                case UpgradeType.DoubleShot:
                    HasDoubleShotgun = true;
                    break;

                case UpgradeType.ExplosiveBullets:
                    HasExplosiveBullets = true;
                    break;

                case UpgradeType.Ricochet:
                    HasRicochetBullets = true;
                    break;

                case UpgradeType.Shield:
                    ShieldCharges += 1;
                    PlayerHealth.Instance?.AddShieldCharge();
                    break;
            }

            OnStatsChanged?.Invoke();
            GetComponent<PlayerShooter>()?.RefreshStats();
            GetComponent<PlayerController>()?.RefreshStats();
        }

        // Chamado quando o jogador absorve um golpe com escudo
        public void ConsumeShield() { if (ShieldCharges > 0) ShieldCharges--; }
    }

    public enum UpgradeType
    {
        Damage, FireRate, Speed, MaxHP, HealNow,
        BulletRange, PiercingBullets, DoubleShot,
        ExplosiveBullets, Ricochet, Shield
    }
}
