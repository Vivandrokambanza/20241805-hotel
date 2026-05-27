using UnityEngine;

namespace WarZone
{
    // Gere os 2 slots de arma + consumíveis (estilo Free Fire)
    public class PlayerInventory : MonoBehaviour
    {
        public static PlayerInventory Instance { get; private set; }

        [Header("Slots")]
        private WeaponData _slot1;          // arma principal (AR, sniper)
        private WeaponData _slot2;          // arma secundária (pistola, shotgun)
        private int        _activeSlot = 0;

        [Header("Consumíveis")]
        private int _healthKits   = 0;
        private int _armorPieces  = 0;
        private int _grenades     = 0;

        [Header("Munição por tipo")]
        private int _ammoAR       = 0;
        private int _ammoShotgun  = 0;
        private int _ammoSniper   = 0;
        private int _ammoPistol   = 90;    // começa com pistola

        public WeaponData ActiveWeapon => _activeSlot == 0 ? _slot1 : _slot2;
        public int HealthKits   => _healthKits;
        public int ArmorPieces  => _armorPieces;
        public int Grenades     => _grenades;

        public event System.Action OnInventoryChanged;

        void Awake()
        {
            if (Instance != null) { Destroy(gameObject); return; }
            Instance = this;
        }

        // ── Apanhar arma ─────────────────────────────────────────────────────
        public bool PickupWeapon(WeaponData weapon)
        {
            if (weapon == null) return false;

            if (_slot1 == null)       { _slot1 = weapon; SwitchSlot(0); }
            else if (_slot2 == null)  { _slot2 = weapon; SwitchSlot(1); }
            else
            {
                // Substitui o slot ativo
                if (_activeSlot == 0) _slot1 = weapon;
                else                  _slot2 = weapon;
            }

            AudioManager.Instance?.PlayPickup();
            OnInventoryChanged?.Invoke();
            UIManager.Instance?.RefreshWeaponSlots(_slot1, _slot2, _activeSlot);
            return true;
        }

        public void SwitchSlot(int slot)
        {
            _activeSlot = slot;
            GetComponent<PlayerShooter>()?.EquipWeapon(ActiveWeapon);
            UIManager.Instance?.RefreshWeaponSlots(_slot1, _slot2, _activeSlot);
        }

        public void ToggleWeapon() => SwitchSlot(_activeSlot == 0 ? 1 : 0);

        // ── Apanhar itens ────────────────────────────────────────────────────
        public void PickupHealthKit()   { _healthKits++;  OnInventoryChanged?.Invoke(); UIManager.Instance?.UpdateItems(_healthKits, _armorPieces, _grenades); }
        public void PickupArmor()       { _armorPieces = Mathf.Min(_armorPieces + 1, 3); OnInventoryChanged?.Invoke(); GetComponent<PlayerHealth>()?.AddArmor(50); UIManager.Instance?.UpdateItems(_healthKits, _armorPieces, _grenades); }
        public void PickupGrenade()     { _grenades++;    OnInventoryChanged?.Invoke(); UIManager.Instance?.UpdateItems(_healthKits, _armorPieces, _grenades); }

        // ── Usar itens ───────────────────────────────────────────────────────
        public void UseHealthKit()
        {
            if (_healthKits <= 0) return;
            _healthKits--;
            PlayerHealth.Instance?.Heal(60);
            AudioManager.Instance?.PlayHealSound();
            UIManager.Instance?.UpdateItems(_healthKits, _armorPieces, _grenades);
        }

        // ── Munição ──────────────────────────────────────────────────────────
        public int GetAmmo(AmmoType type) => type switch
        {
            AmmoType.AR      => _ammoAR,
            AmmoType.Shotgun => _ammoShotgun,
            AmmoType.Sniper  => _ammoSniper,
            AmmoType.Pistol  => _ammoPistol,
            _ => 0
        };

        public void AddAmmo(AmmoType type, int amount)
        {
            switch (type)
            {
                case AmmoType.AR:      _ammoAR      += amount; break;
                case AmmoType.Shotgun: _ammoShotgun += amount; break;
                case AmmoType.Sniper:  _ammoSniper  += amount; break;
                case AmmoType.Pistol:  _ammoPistol  += amount; break;
            }
            OnInventoryChanged?.Invoke();
        }

        public bool SpendAmmo(AmmoType type, int amount)
        {
            int current = GetAmmo(type);
            if (current < amount) return false;
            switch (type)
            {
                case AmmoType.AR:      _ammoAR      -= amount; break;
                case AmmoType.Shotgun: _ammoShotgun -= amount; break;
                case AmmoType.Sniper:  _ammoSniper  -= amount; break;
                case AmmoType.Pistol:  _ammoPistol  -= amount; break;
            }
            return true;
        }
    }

    public enum AmmoType { AR, Shotgun, Sniper, Pistol }
}
