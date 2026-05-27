using UnityEngine;
using System.Collections.Generic;

namespace WarZone
{
    // Distribui armas, munição, coletes e kits pelo mapa no início da partida
    public class LootManager : MonoBehaviour
    {
        public static LootManager Instance { get; private set; }

        [Header("Weapon Prefabs")]
        [SerializeField] private GameObject _pistolPickup;
        [SerializeField] private GameObject _arPickup;       // M4A1 / SCAR
        [SerializeField] private GameObject _shotgunPickup;
        [SerializeField] private GameObject _sniperPickup;
        [SerializeField] private GameObject _smgPickup;      // MP40

        [Header("Item Prefabs")]
        [SerializeField] private GameObject _healthKitPrefab;
        [SerializeField] private GameObject _armorVestPrefab;
        [SerializeField] private GameObject _ammoBoxPrefab;
        [SerializeField] private GameObject _grenadePickup;

        [Header("Spawn Settings")]
        [SerializeField] private int   _weaponsPerZone  = 8;
        [SerializeField] private int   _itemsPerZone    = 15;
        [SerializeField] private float _mapRadius       = 190f;
        [SerializeField] private LayerMask _obstacleLayer;

        // Zonas de loot de alta qualidade (ex: cidade central)
        [SerializeField] private Transform[] _hotDropZones;

        private List<GameObject> _spawnedItems = new();

        void Awake()
        {
            if (Instance != null) { Destroy(gameObject); return; }
            Instance = this;
        }

        void Start() => SpawnAllLoot();

        private void SpawnAllLoot()
        {
            // Loot normal espalhado pelo mapa
            SpawnRandomLoot(_mapRadius, _weaponsPerZone * 3, _itemsPerZone * 3, false);

            // Hot drops: zonas com loot de nível mais alto
            if (_hotDropZones != null)
            {
                foreach (var zone in _hotDropZones)
                    SpawnAroundPoint(zone.position, 20f, _weaponsPerZone, _itemsPerZone, true);
            }
        }

        private void SpawnRandomLoot(float radius, int weapons, int items, bool highTier)
        {
            SpawnAroundPoint(Vector3.zero, radius, weapons, items, highTier);
        }

        private void SpawnAroundPoint(Vector3 center, float radius, int weapons, int items, bool highTier)
        {
            // Armas
            for (int i = 0; i < weapons; i++)
            {
                Vector3 pos = RandomPoint(center, radius);
                GameObject prefab = ChooseWeaponPrefab(highTier);
                if (prefab != null)
                    _spawnedItems.Add(Instantiate(prefab, pos, Quaternion.identity));
            }

            // Itens (kits, coletes, munição)
            for (int i = 0; i < items; i++)
            {
                Vector3 pos = RandomPoint(center, radius);
                GameObject prefab = ChooseItemPrefab();
                if (prefab != null)
                    _spawnedItems.Add(Instantiate(prefab, pos, Quaternion.identity));
            }

            // Granadas (menos comuns)
            for (int i = 0; i < items / 5; i++)
            {
                Vector3 pos = RandomPoint(center, radius);
                if (_grenadePickup != null)
                    _spawnedItems.Add(Instantiate(_grenadePickup, pos, Quaternion.identity));
            }
        }

        private Vector3 RandomPoint(Vector3 center, float radius)
        {
            for (int attempt = 0; attempt < 10; attempt++)
            {
                Vector2 rand = Random.insideUnitCircle * radius;
                Vector3 pos  = center + new Vector3(rand.x, rand.y, 0);
                // Verifica que não está dentro de uma parede
                if (!Physics2D.OverlapCircle(pos, 0.5f, _obstacleLayer))
                    return pos;
            }
            return center;
        }

        private GameObject ChooseWeaponPrefab(bool highTier)
        {
            float r = Random.value;
            if (highTier)
            {
                if (r < 0.25f) return _sniperPickup;
                if (r < 0.55f) return _arPickup;
                if (r < 0.75f) return _shotgunPickup;
                if (r < 0.90f) return _smgPickup;
                return _pistolPickup;
            }
            else
            {
                if (r < 0.10f) return _sniperPickup;
                if (r < 0.30f) return _arPickup;
                if (r < 0.50f) return _shotgunPickup;
                if (r < 0.70f) return _smgPickup;
                return _pistolPickup;
            }
        }

        private GameObject ChooseItemPrefab()
        {
            float r = Random.value;
            if (r < 0.40f) return _healthKitPrefab;
            if (r < 0.65f) return _ammoBoxPrefab;
            return _armorVestPrefab;
        }
    }
}
