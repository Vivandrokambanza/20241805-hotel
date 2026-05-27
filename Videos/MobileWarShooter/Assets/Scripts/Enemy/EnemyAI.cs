using UnityEngine;

namespace WarZone
{
    [RequireComponent(typeof(Rigidbody2D))]
    public class EnemyAI : EnemyBase
    {
        [Header("Movement")]
        [SerializeField] private float _moveSpeed   = 2.5f;
        [SerializeField] private float _stopRange   = 1.2f;  // distância para parar e atacar

        [Header("Ranged Attack (deixar null para melee only)")]
        [SerializeField] private GameObject _bulletPrefab;
        [SerializeField] private float      _attackRange  = 6f;
        [SerializeField] private float      _attackCooldown = 2f;
        [SerializeField] private int        _bulletDamage  = 8;
        [SerializeField] private float      _bulletSpeed   = 5f;

        private Rigidbody2D _rb;
        private Transform   _player;
        private float       _attackTimer;
        private bool        _isRanged;

        protected override void Awake()
        {
            base.Awake();
            _rb       = GetComponent<Rigidbody2D>();
            _isRanged = _bulletPrefab != null;
        }

        void Start()
        {
            _player = PlayerController.Instance?.transform;
        }

        void Update()
        {
            if (!IsAlive || _player == null) return;
            if (GameManager.Instance != null && GameManager.Instance.GameOver) return;

            float dist = Vector2.Distance(transform.position, _player.position);

            // Mover em direção ao jogador (exceto se for sniper e já está em range)
            if (dist > _stopRange && !(_isRanged && dist < _attackRange))
                MoveTowardsPlayer();
            else
                _rb.velocity = Vector2.zero;

            // Rodar para olhar para o jogador
            Vector2 dir = (_player.position - transform.position).normalized;
            float angle = Mathf.Atan2(dir.y, dir.x) * Mathf.Rad2Deg - 90f;
            transform.rotation = Quaternion.AngleAxis(angle, Vector3.forward);

            // Ataque à distância
            if (_isRanged && dist < _attackRange)
            {
                _attackTimer += Time.deltaTime;
                if (_attackTimer >= _attackCooldown)
                {
                    ShootAtPlayer(dir);
                    _attackTimer = 0f;
                }
            }
        }

        private void MoveTowardsPlayer()
        {
            Vector2 dir = (_player.position - transform.position).normalized;
            _rb.velocity = dir * _moveSpeed;
        }

        private void ShootAtPlayer(Vector2 dir)
        {
            if (_bulletPrefab == null) return;
            var go = Instantiate(_bulletPrefab, transform.position + (Vector3)dir * 0.5f, Quaternion.identity);
            if (go.TryGetComponent<Bullet>(out var b))
                b.Init(dir, _bulletSpeed, _bulletDamage, isPlayerBullet: false);
        }

        public override void ScaleForWave(int wave)
        {
            base.ScaleForWave(wave);
            _moveSpeed      = Mathf.Min(_moveSpeed + wave * 0.08f, 5f);
            _attackCooldown = Mathf.Max(_attackCooldown - wave * 0.05f, 0.6f);
        }
    }
}
