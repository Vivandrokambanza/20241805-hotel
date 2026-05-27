using UnityEngine;
using System.Collections;
using System.Collections.Generic;

namespace WarZone
{
    public class WaveManager : MonoBehaviour
    {
        public static WaveManager Instance { get; private set; }

        [Header("Enemy Prefabs")]
        [SerializeField] private GameObject _infantryPrefab;
        [SerializeField] private GameObject _heavyPrefab;
        [SerializeField] private GameObject _sniperPrefab;

        [Header("Spawn Settings")]
        [SerializeField] private float _spawnRadius     = 10f;  // raio fora do ecrã
        [SerializeField] private float _timeBetweenWaves = 3f;
        [SerializeField] private float _spawnDelay       = 0.3f; // delay entre spawns

        private int           _currentWave;
        private int           _aliveEnemies;
        private List<EnemyBase> _activeEnemies = new();

        public int  CurrentWave  => _currentWave;
        public int  AliveEnemies => _aliveEnemies;

        public event System.Action<int> OnWaveComplete;

        void Awake()
        {
            if (Instance != null) { Destroy(gameObject); return; }
            Instance = this;
        }

        void Start() => StartCoroutine(StartFirstWave());

        private IEnumerator StartFirstWave()
        {
            yield return new WaitForSeconds(1.5f);
            StartNextWave();
        }

        public void StartNextWave()
        {
            _currentWave++;
            GameManager.Instance?.StartNextWave();
            AudioManager.Instance?.PlayWaveStart();
            UIManager.Instance?.ShowWaveBanner(_currentWave);
            StartCoroutine(SpawnWave(_currentWave));
        }

        // ── Composição da onda ────────────────────────────────────────────────
        private IEnumerator SpawnWave(int wave)
        {
            var composition = GetWaveComposition(wave);

            foreach (var (prefab, count) in composition)
            {
                for (int i = 0; i < count; i++)
                {
                    SpawnEnemy(prefab, wave);
                    yield return new WaitForSeconds(_spawnDelay);
                }
            }
        }

        // Define quantos inimigos de cada tipo aparecem por onda
        private List<(GameObject prefab, int count)> GetWaveComposition(int wave)
        {
            var list = new List<(GameObject, int)>();
            int base_ = wave + 2;

            // Infantaria: sempre presente, escala linearmente
            list.Add((_infantryPrefab, base_));

            // Heavy: aparece a partir da onda 3
            if (wave >= 3 && _heavyPrefab != null)
                list.Add((_heavyPrefab, Mathf.Max(1, wave / 3)));

            // Sniper: aparece a partir da onda 5
            if (wave >= 5 && _sniperPrefab != null)
                list.Add((_sniperPrefab, Mathf.Max(1, wave / 5)));

            return list;
        }

        private void SpawnEnemy(GameObject prefab, int wave)
        {
            if (prefab == null) return;

            // Spawn num ponto aleatório fora do ecrã
            Vector2 spawnPos = (Vector2)Camera.main.transform.position
                               + Random.insideUnitCircle.normalized * _spawnRadius;

            var go = Instantiate(prefab, spawnPos, Quaternion.identity);
            if (go.TryGetComponent<EnemyBase>(out var enemy))
            {
                enemy.ScaleForWave(wave);
                enemy.OnDied += OnEnemyDied;
                _activeEnemies.Add(enemy);
                _aliveEnemies++;
            }
        }

        private void OnEnemyDied(EnemyBase enemy)
        {
            _activeEnemies.Remove(enemy);
            _aliveEnemies--;

            if (_aliveEnemies <= 0) StartCoroutine(WaveComplete());
        }

        private IEnumerator WaveComplete()
        {
            AudioManager.Instance?.PlayWaveEnd();
            OnWaveComplete?.Invoke(_currentWave);
            UIManager.Instance?.ShowWaveCompletePanel(_currentWave);

            yield return new WaitForSeconds(_timeBetweenWaves);
            StartNextWave();
        }
    }
}
