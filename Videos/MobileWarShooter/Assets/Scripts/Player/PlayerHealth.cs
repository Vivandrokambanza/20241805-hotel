using UnityEngine;
using System.Collections;

namespace WarZone
{
    public class PlayerHealth : MonoBehaviour
    {
        public static PlayerHealth Instance { get; private set; }

        [Header("Stats")]
        [SerializeField] private int   _maxHp         = 100;
        [SerializeField] private float _invincibleTime = 0.4f;  // iframes após hit

        [Header("Effects")]
        [SerializeField] private SpriteRenderer _sprite;
        [SerializeField] private GameObject     _deathEffect;

        public int  MaxHp        { get; private set; }
        public int  CurrentHp    { get; private set; }
        public bool IsInvincible { get; private set; }
        public bool IsAlive      => CurrentHp > 0;

        public event System.Action<int, int> OnHpChanged;   // (current, max)
        public event System.Action           OnDeath;

        void Awake()
        {
            if (Instance != null) { Destroy(gameObject); return; }
            Instance = this;
            MaxHp    = _maxHp;
            CurrentHp = _maxHp;
        }

        // ── Dano ─────────────────────────────────────────────────────────────
        public void TakeDamage(int amount)
        {
            if (!IsAlive || IsInvincible) return;

            CurrentHp = Mathf.Max(0, CurrentHp - amount);
            OnHpChanged?.Invoke(CurrentHp, MaxHp);
            AudioManager.Instance?.PlayHit();

            if (CurrentHp <= 0)
            {
                Die();
                return;
            }

            // Iframes + flash vermelho
            StartCoroutine(InvincibilityFlash(_invincibleTime));
        }

        public void Heal(int amount)
        {
            CurrentHp = Mathf.Min(MaxHp, CurrentHp + amount);
            OnHpChanged?.Invoke(CurrentHp, MaxHp);
        }

        // Chamado pelo dash — período de invencibilidade
        public void SetInvincible(float duration) => StartCoroutine(SetInvincibleFor(duration));

        public void UpgradeMaxHp(int bonus)
        {
            MaxHp     += bonus;
            CurrentHp += bonus;
            OnHpChanged?.Invoke(CurrentHp, MaxHp);
        }

        // Revive com 50% HP (após anúncio rewarded)
        public void Revive()
        {
            CurrentHp = MaxHp / 2;
            OnHpChanged?.Invoke(CurrentHp, MaxHp);
            transform.position = Vector3.zero;
        }

        // ── Morte ─────────────────────────────────────────────────────────────
        private void Die()
        {
            AudioManager.Instance?.PlayDeath();
            if (_deathEffect) Instantiate(_deathEffect, transform.position, Quaternion.identity);
            OnDeath?.Invoke();
            GameManager.Instance?.TriggerGameOver();
            gameObject.SetActive(false);
        }

        // ── Coroutines ────────────────────────────────────────────────────────
        private IEnumerator InvincibilityFlash(float duration)
        {
            IsInvincible = true;
            float t = 0;
            while (t < duration)
            {
                if (_sprite) _sprite.color = Color.red;
                yield return new WaitForSeconds(0.05f);
                if (_sprite) _sprite.color = Color.white;
                yield return new WaitForSeconds(0.05f);
                t += 0.1f;
            }
            IsInvincible = false;
        }

        private IEnumerator SetInvincibleFor(float duration)
        {
            IsInvincible = true;
            yield return new WaitForSeconds(duration);
            IsInvincible = false;
        }
    }
}
