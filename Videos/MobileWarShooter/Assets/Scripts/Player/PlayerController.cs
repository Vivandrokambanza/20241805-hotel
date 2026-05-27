using UnityEngine;

namespace WarZone
{
    // Controla movimento do jogador pelo joystick virtual
    [RequireComponent(typeof(Rigidbody2D))]
    public class PlayerController : MonoBehaviour
    {
        public static PlayerController Instance { get; private set; }

        [Header("Movement")]
        [SerializeField] private float _moveSpeed    = 5f;
        [SerializeField] private float _dashSpeed    = 18f;
        [SerializeField] private float _dashDuration = 0.18f;
        [SerializeField] private float _dashCooldown = 1.2f;

        [Header("References")]
        [SerializeField] private VirtualJoystick _joystick;
        [SerializeField] private TrailRenderer   _dashTrail;

        private Rigidbody2D _rb;
        private PlayerHealth _health;

        private bool  _isDashing;
        private float _dashTimer;
        private float _dashCooldownTimer;
        private Vector2 _dashDir;

        public bool  IsDashing => _isDashing;
        public bool  CanDash   => _dashCooldownTimer <= 0f && !_isDashing;
        public Vector2 MoveDir => _joystick != null ? _joystick.Direction : Vector2.zero;

        void Awake()
        {
            if (Instance != null) { Destroy(gameObject); return; }
            Instance = this;
            _rb      = GetComponent<Rigidbody2D>();
            _health  = GetComponent<PlayerHealth>();
        }

        void Update()
        {
            if (_dashCooldownTimer > 0f) _dashCooldownTimer -= Time.deltaTime;
            if (_isDashing)
            {
                _dashTimer -= Time.deltaTime;
                if (_dashTimer <= 0f) EndDash();
            }
        }

        void FixedUpdate()
        {
            if (_isDashing)
            {
                _rb.velocity = _dashDir * _dashSpeed;
                return;
            }

            Vector2 dir = MoveDir;
            _rb.velocity = dir * _moveSpeed;

            // Rotacionar o sprite para a direção de movimento
            if (dir.sqrMagnitude > 0.01f)
                transform.up = dir;
        }

        // Chamado pelo botão de dash na UI
        public void StartDash()
        {
            if (!CanDash) return;

            Vector2 dir = MoveDir;
            if (dir.sqrMagnitude < 0.01f)
                dir = transform.up;  // dash para a frente se estiver parado

            _isDashing            = true;
            _dashDir              = dir.normalized;
            _dashTimer            = _dashDuration;
            _dashCooldownTimer    = _dashCooldown;

            if (_dashTrail) _dashTrail.emitting = true;
            _health?.SetInvincible(_dashDuration + 0.05f);
            AudioManager.Instance?.PlayDash();
        }

        private void EndDash()
        {
            _isDashing = false;
            if (_dashTrail) _dashTrail.emitting = false;
        }
    }
}
