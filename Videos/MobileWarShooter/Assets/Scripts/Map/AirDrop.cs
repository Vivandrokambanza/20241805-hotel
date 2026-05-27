using UnityEngine;
using System.Collections;

namespace WarZone
{
    // Caixote de airdrop com loot de nível máximo — cai de 30 em 30 segundos
    public class AirDrop : MonoBehaviour
    {
        [Header("Settings")]
        [SerializeField] private float _dropInterval = 90f;
        [SerializeField] private float _fallSpeed    = 4f;
        [SerializeField] private float _landY        = 0f;

        [Header("Loot")]
        [SerializeField] private GameObject[] _legendaryWeapons;
        [SerializeField] private GameObject   _cratePrefab;
        [SerializeField] private GameObject   _smokeEffect;

        private static AirDrop _instance;

        void Start()
        {
            _instance = this;
            StartCoroutine(AirDropCycle());
        }

        private IEnumerator AirDropCycle()
        {
            while (true)
            {
                yield return new WaitForSeconds(_dropInterval);
                if (MatchManager.Instance?.State == MatchState.Playing)
                    DropCrate();
            }
        }

        private void DropCrate()
        {
            // Posição aleatória dentro da safe zone atual
            Vector2 center = SafeZone.Instance?.Center ?? Vector2.zero;
            float   radius = (SafeZone.Instance?.CurrentRadius ?? 50f) * 0.6f;
            Vector2 rand   = Random.insideUnitCircle * radius;
            Vector3 target = new Vector3(center.x + rand.x, center.y + rand.y, 0);

            // Começa no topo do ecrã
            Vector3 startPos = target + Vector3.up * 25f;

            if (_cratePrefab)
            {
                var crate = Instantiate(_cratePrefab, startPos, Quaternion.identity);
                StartCoroutine(FallCrate(crate, target));
            }

            UIManager.Instance?.ShowAirDropAlert(target);
        }

        private IEnumerator FallCrate(GameObject crate, Vector3 target)
        {
            while (crate.transform.position.y > target.y)
            {
                crate.transform.position = Vector3.MoveTowards(
                    crate.transform.position, target, _fallSpeed * Time.deltaTime);
                yield return null;
            }
            crate.transform.position = target;

            // Efeito de aterragem
            if (_smokeEffect) Instantiate(_smokeEffect, target, Quaternion.identity);

            // Adiciona script de loot ao crate
            var loot = crate.AddComponent<AirDropCrate>();
            loot.SetWeapons(_legendaryWeapons);

            AudioManager.Instance?.PlayAirDrop();
        }
    }

    // Caixote que o jogador pode abrir (pressionar E perto)
    public class AirDropCrate : MonoBehaviour
    {
        private GameObject[] _weapons;
        private bool _opened;

        public void SetWeapons(GameObject[] w) => _weapons = w;

        void OnTriggerEnter2D(Collider2D other)
        {
            if (_opened || !other.CompareTag("Player")) return;
            Open();
        }

        private void Open()
        {
            _opened = true;
            if (_weapons == null || _weapons.Length == 0) return;
            // Spawn de 1-2 armas lendárias
            int count = Random.Range(1, 3);
            for (int i = 0; i < count; i++)
            {
                var w = _weapons[Random.Range(0, _weapons.Length)];
                Vector2 off = Random.insideUnitCircle * 0.8f;
                if (w) Instantiate(w, transform.position + (Vector3)off, Quaternion.identity);
            }
            GetComponent<SpriteRenderer>().color = Color.gray;
        }
    }
}
