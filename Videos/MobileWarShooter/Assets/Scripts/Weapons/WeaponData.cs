using UnityEngine;

namespace WarZone
{
    // ScriptableObject: clica com direito > Create > WarZone > WeaponData
    [CreateAssetMenu(menuName = "WarZone/WeaponData", fileName = "NewWeapon")]
    public class WeaponData : ScriptableObject
    {
        [Header("Info")]
        public string weaponName  = "M4A1";
        public Sprite icon;

        [Header("Combat")]
        public int   damage       = 25;
        public float fireRate     = 4f;    // tiros por segundo
        public int   magazineSize = 30;
        public float reloadTime   = 1.8f;
        public float bulletSpeed  = 12f;
        public float range        = 8f;

        [Header("Upgrade Cost")]
        public int upgradeCostDamage   = 200;
        public int upgradeCostFireRate = 300;
        public int upgradeCostMagazine = 150;
    }
}
