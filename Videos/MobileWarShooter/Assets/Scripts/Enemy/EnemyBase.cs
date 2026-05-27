using UnityEngine;

namespace WarZone
{
    public enum EnemyType { Infantry, Heavy, Sniper, Bomber }

    public class EnemyBase : MonoBehaviour
    {
        [Header("Stats")]
        [SerializeField] protected int    _maxHp       = 50;
        [SerializeField] protected int    _damage      = 10;
        [SerializeField] protected int    _coinDrop    = 5;
        [SerializeField] protected int    _scoreDrop   = 100;
        [SerializeField] protected EnemyType _type     = EnemyType.Infantry;

        [Header("Effects")]
        [SerializeField] protected GameObject _deathEffect;
        [SerializeField] protected GameObject _coinPrefab;
        [SerializeField] protected SpriteRenderer _sprite;

        public int       CurrentHp  { get; protected set; }
        public bool      IsAlive    => CurrentHp > 0;
        public EnemyType Type       => _type;

        public event System.Action<EnemyBase> OnDied;

        protected virtual void Awake()
        {
            CurrentHp = _maxHp;
            if (_sprite == null) _sprite = GetComponent<SpriteRenderer>();
        }

        // Chamado pelo WaveManager para escalar dificuldade por onda
        public virtual void ScaleForWave(int wave)
        {
            float mult = 1f + (wave - 1) * 0.15f;
            _maxHp    = Mathf.RoundToInt(_maxHp  * mult);
            _damage   = Mathf.RoundToInt(_damage * mult);
            _coinDrop = Mathf.RoundToInt(_coinDrop * (1f + (wave - 1) * 0.1f));
            CurrentHp = _maxHp;
        }

        // ── Dano ─────────────────────────────────────────────────────────────
        public virtual void TakeDamage(int amount)
        {
            if (!IsAlive) return;
            CurrentHp -= amount;
            if (_sprite) StartCoroutine(FlashRed());
            if (CurrentHp <= 0) Die();
        }

        protected virtual void Die()
        {
            CurrentHp = 0;

            // Efeito visual de morte
            if (_deathEffect)
                Instantiate(_deathEffect, transform.position, Quaternion.identity);

            // Spawn de moedas
            for (int i = 0; i < _coinDrop; i++)
            {
                Vector2 offset = Random.insideUnitCircle * 0.5f;
                if (_coinPrefab)
                    Instantiate(_coinPrefab,
                        transform.position + (Vector3)offset,
                        Quaternion.identity);
            }

            GameManager.Instance?.AddScore(_scoreDrop);
            OnDied?.Invoke(this);
            Destroy(gameObject, 0.05f);
        }

        // Flash vermelho ao tomar dano
        private System.Collections.IEnumerator FlashRed()
        {
            if (_sprite == null) yield break;
            _sprite.color = Color.red;
            yield return new WaitForSeconds(0.08f);
            _sprite.color = Color.white;
        }

        // Dano por contacto com o jogador
        protected virtual void OnTriggerEnter2D(Collider2D other)
        {
            if (!IsAlive) return;
            if (other.CompareTag("Player"))
                other.GetComponent<PlayerHealth>()?.TakeDamage(_damage);
        }
    }
}
