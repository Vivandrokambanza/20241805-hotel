using UnityEngine;
using System.Collections;

namespace WarZone
{
    // Auto-mira no inimigo mais próximo e dispara automaticamente
    public class PlayerShooter : MonoBehaviour
    {
        [Header("Weapon")]
        [SerializeField] private WeaponData _weapon;
        [SerializeField] private Transform  _muzzle;       // ponto de saída da bala
        [SerializeField] private GameObject _bulletPrefab;

        [Header("Auto-Aim")]
        [SerializeField] private float _aimRange   = 8f;
        [SerializeField] private LayerMask _enemyLayer;

        private float _fireTimer;
        private int   _currentAmmo;
        private bool  _isReloading;

        // Estatísticas dinâmicas (melhoram com upgrades)
        private int   _damage;
        private float _fireRate;
        private int   _magazineSize;
        private float _reloadTime;
        private float _bulletSpeed;

        public int  CurrentAmmo  => _currentAmmo;
        public int  MagazineSize => _magazineSize;
        public bool IsReloading  => _isReloading;

        public event System.Action<int, int> OnAmmoChanged;  // (current, max)

        void Start()
        {
            if (_weapon == null) return;
            _damage      = _weapon.damage;
            _fireRate    = _weapon.fireRate;
            _magazineSize = _weapon.magazineSize;
            _reloadTime  = _weapon.reloadTime;
            _bulletSpeed = _weapon.bulletSpeed;
            _currentAmmo = _magazineSize;
        }

        void Update()
        {
            if (_isReloading || GameManager.Instance == null || GameManager.Instance.GameOver) return;

            _fireTimer += Time.deltaTime;
            if (_fireTimer < 1f / _fireRate) return;

            Transform target = FindNearestEnemy();
            if (target == null) return;

            Shoot(target);
            _fireTimer = 0f;
        }

        // ── Auto-mira ────────────────────────────────────────────────────────
        private Transform FindNearestEnemy()
        {
            Collider2D[] hits = Physics2D.OverlapCircleAll(transform.position, _aimRange, _enemyLayer);
            Transform nearest  = null;
            float minDist      = float.MaxValue;

            foreach (var h in hits)
            {
                float d = Vector2.Distance(transform.position, h.transform.position);
                if (d < minDist) { minDist = d; nearest = h.transform; }
            }
            return nearest;
        }

        // ── Tiro ─────────────────────────────────────────────────────────────
        private void Shoot(Transform target)
        {
            if (_currentAmmo <= 0) { StartCoroutine(Reload()); return; }

            Vector2 dir = (target.position - _muzzle.position).normalized;
            // Rodar o muzzle para o alvo
            float angle = Mathf.Atan2(dir.y, dir.x) * Mathf.Rad2Deg;
            _muzzle.rotation = Quaternion.AngleAxis(angle, Vector3.forward);

            var go = Instantiate(_bulletPrefab, _muzzle.position, _muzzle.rotation);
            if (go.TryGetComponent<Bullet>(out var b))
            {
                b.Init(dir, _bulletSpeed, _damage, isPlayerBullet: true);
            }

            _currentAmmo--;
            OnAmmoChanged?.Invoke(_currentAmmo, _magazineSize);
            AudioManager.Instance?.PlayShoot();

            if (_currentAmmo <= 0) StartCoroutine(Reload());
        }

        private IEnumerator Reload()
        {
            if (_isReloading) yield break;
            _isReloading = true;
            AudioManager.Instance?.PlayReload();
            yield return new WaitForSeconds(_reloadTime);
            _currentAmmo = _magazineSize;
            _isReloading = false;
            OnAmmoChanged?.Invoke(_currentAmmo, _magazineSize);
        }

        // ── Upgrades ─────────────────────────────────────────────────────────
        public void UpgradeDamage(int bonus)   => _damage      += bonus;
        public void UpgradeFireRate(float bonus)=> _fireRate    += bonus;
        public void UpgradeMagazine(int bonus) => _magazineSize += bonus;

        void OnDrawGizmosSelected()
        {
            Gizmos.color = Color.yellow;
            Gizmos.DrawWireSphere(transform.position, _aimRange);
        }
    }
}
