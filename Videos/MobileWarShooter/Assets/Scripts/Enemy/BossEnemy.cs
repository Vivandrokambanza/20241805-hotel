using UnityEngine;
using System.Collections;

namespace WarZone
{
    // Boss aparece a cada 5 vagas — tem fases e ataques especiais
    public class BossEnemy : EnemyBase
    {
        [Header("Boss Settings")]
        [SerializeField] private string     _bossName      = "GENERAL";
        [SerializeField] private int        _phase2HpPct   = 50;    // % HP para entrar em fase 2
        [SerializeField] private float      _chargeSpeed   = 12f;
        [SerializeField] private GameObject _projectilePrefab;
        [SerializeField] private GameObject _explosionPrefab;
        [SerializeField] private int        _bulletCount   = 8;     // rajada circular

        [Header("Health Bar")]
        [SerializeField] private UnityEngine.UI.Slider _bossHpSlider;

        private bool  _phase2Active = false;
        private float _attackTimer  = 0f;
        private int   _attackIndex  = 0;
        private Transform _player;

        // Padrão de ataques: 0=rush, 1=spray circular, 2=3 balas em leque
        private readonly int[] _attackPattern = { 0, 1, 2, 1, 0, 2 };

        protected override void Awake()
        {
            base.Awake();
            _player = PlayerController.Instance?.transform;
        }

        void Start()
        {
            // Mostra barra de HP do boss no HUD
            UIManager.Instance?.ShowBossHPBar(_bossName, _maxHp);
            AudioManager.Instance?.PlayBossMusic();
        }

        void Update()
        {
            if (!IsAlive || _player == null) return;

            _attackTimer += Time.deltaTime;
            float cooldown = _phase2Active ? 1.5f : 2.5f;

            if (_attackTimer >= cooldown)
            {
                ExecuteAttack(_attackPattern[_attackIndex % _attackPattern.Length]);
                _attackIndex++;
                _attackTimer = 0f;
            }

            // Fase 2: move mais rápido e fica vermelho
            if (!_phase2Active && CurrentHp <= _maxHp * _phase2HpPct / 100)
                EnterPhase2();
        }

        private void ExecuteAttack(int index)
        {
            switch (index)
            {
                case 0: StartCoroutine(ChargeAttack());   break;   // rush para o jogador
                case 1: CircularBulletSpray();            break;   // 8 balas em círculo
                case 2: FanBulletAttack();                break;   // 3 balas em leque
            }
        }

        // ── Ataques ──────────────────────────────────────────────────────────

        private IEnumerator ChargeAttack()
        {
            if (_player == null) yield break;
            Vector2 target = _player.position;
            Vector2 dir    = (target - (Vector2)transform.position).normalized;
            float  elapsed = 0f;
            AudioManager.Instance?.PlayBossRoar();

            while (elapsed < 0.4f)
            {
                transform.Translate(dir * _chargeSpeed * Time.deltaTime);
                elapsed += Time.deltaTime;
                yield return null;
            }
        }

        private void CircularBulletSpray()
        {
            if (_projectilePrefab == null) return;
            for (int i = 0; i < _bulletCount; i++)
            {
                float angle = i * (360f / _bulletCount);
                Vector2 dir = new Vector2(
                    Mathf.Cos(angle * Mathf.Deg2Rad),
                    Mathf.Sin(angle * Mathf.Deg2Rad));
                SpawnProjectile(dir);
            }
        }

        private void FanBulletAttack()
        {
            if (_player == null || _projectilePrefab == null) return;
            Vector2 toPlayer = (_player.position - transform.position).normalized;
            float base_ = Mathf.Atan2(toPlayer.y, toPlayer.x) * Mathf.Rad2Deg;
            for (int i = -1; i <= 1; i++)
            {
                float angle = (base_ + i * 20f) * Mathf.Deg2Rad;
                SpawnProjectile(new Vector2(Mathf.Cos(angle), Mathf.Sin(angle)));
            }
        }

        private void SpawnProjectile(Vector2 dir)
        {
            var go = Instantiate(_projectilePrefab,
                transform.position + (Vector3)dir * 1.2f, Quaternion.identity);
            go.GetComponent<Bullet>()?.Init(dir, 7f, _damage, isPlayerBullet: false);
        }

        // ── Fase 2 ───────────────────────────────────────────────────────────
        private void EnterPhase2()
        {
            _phase2Active = true;
            if (_sprite) _sprite.color = new Color(1f, 0.4f, 0.4f);
            if (_explosionPrefab) Instantiate(_explosionPrefab, transform.position, Quaternion.identity);
            UIManager.Instance?.ShowBossPhase2Banner(_bossName);
        }

        // ── Override morte ───────────────────────────────────────────────────
        protected override void Die()
        {
            UIManager.Instance?.HideBossHPBar();
            AudioManager.Instance?.PlayGameMusic();
            GameManager.Instance?.AddScore(1000);
            GameManager.Instance?.AddCoins(100);
            base.Die();
        }

        public override void TakeDamage(int amount)
        {
            base.TakeDamage(amount);
            UIManager.Instance?.UpdateBossHP(CurrentHp, _maxHp);
        }
    }
}
