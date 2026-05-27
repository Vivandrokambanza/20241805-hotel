using UnityEngine;
using System.Collections;

namespace WarZone
{
    // Safe zone — círculo que encolhe ao longo do tempo (mecânica core do BR)
    public class SafeZone : MonoBehaviour
    {
        public static SafeZone Instance { get; private set; }

        [Header("Zone Phases")]
        [SerializeField] private float[] _phaseRadii    = { 200f, 120f, 70f, 35f, 15f, 5f };
        [SerializeField] private float[] _phaseDurations = {  90f,  60f, 50f, 40f, 30f, 20f }; // segundos de espera
        [SerializeField] private float[] _shrinkTimes   = {  30f,  25f, 20f, 15f, 10f,  8f }; // segundos a encolher
        [SerializeField] private int     _damagePerSec  = 1;  // dano/segundo fora da zona
        [SerializeField] private int     _damageScale   = 2;  // multiplica por fase

        [Header("Visual")]
        [SerializeField] private LineRenderer _zoneBorderRenderer;
        [SerializeField] private int          _borderSegments = 64;
        [SerializeField] private Color        _safeColor   = new Color(0.3f, 0.6f, 1f, 0.8f);
        [SerializeField] private Color        _dangerColor = Color.red;

        public float CurrentRadius  { get; private set; }
        public Vector2 Center       { get; private set; }
        public int     CurrentPhase { get; private set; }
        public bool    IsShrinking  { get; private set; }

        private float _targetRadius;
        private float _shrinkSpeed;
        private bool  _started;

        public event System.Action<int> OnPhaseChanged;

        void Awake()
        {
            if (Instance != null) { Destroy(gameObject); return; }
            Instance = this;
            CurrentRadius = _phaseRadii[0];
            _targetRadius = CurrentRadius;
            DrawBorder();
        }

        void Update()
        {
            if (!_started) return;

            // Encolhe gradualmente
            if (IsShrinking && CurrentRadius > _targetRadius)
            {
                CurrentRadius = Mathf.MoveTowards(CurrentRadius, _targetRadius, _shrinkSpeed * Time.deltaTime);
                DrawBorder();
            }

            // Dano ao jogador fora da zona
            DamageOutsidePlayers();
        }

        public void StartShrinking() { _started = true; StartCoroutine(ZoneCycle()); }

        private IEnumerator ZoneCycle()
        {
            for (int phase = 0; phase < _phaseRadii.Length; phase++)
            {
                CurrentPhase = phase + 1;
                OnPhaseChanged?.Invoke(CurrentPhase);
                UIManager.Instance?.UpdateZonePhase(CurrentPhase, _phaseDurations[phase]);

                yield return new WaitForSeconds(_phaseDurations[phase]);

                // Escolhe novo centro dentro da zona atual
                Vector2 offset = Random.insideUnitCircle * (CurrentRadius * 0.3f);
                Center         = (Vector2)transform.position + offset;
                _targetRadius  = _phaseRadii[phase];
                _shrinkSpeed   = (CurrentRadius - _targetRadius) / _shrinkTimes[phase];
                IsShrinking    = true;

                yield return new WaitForSeconds(_shrinkTimes[phase]);
                IsShrinking = false;
            }
        }

        // ── Dano fora da zona ────────────────────────────────────────────────
        private float _damageTimer;
        private void DamageOutsidePlayers()
        {
            _damageTimer += Time.deltaTime;
            if (_damageTimer < 1f) return;
            _damageTimer = 0f;

            int dmg = _damagePerSec * (int)Mathf.Pow(_damageScale, CurrentPhase - 1);

            // Dano no jogador
            var ph = PlayerHealth.Instance;
            if (ph != null && ph.IsAlive)
            {
                float dist = Vector2.Distance(
                    (Vector2)PlayerController.Instance.transform.position, Center);
                if (dist > CurrentRadius) ph.TakeDamage(dmg);
            }
        }

        public bool IsInsideZone(Vector2 pos) =>
            Vector2.Distance(pos, Center) <= CurrentRadius;

        // ── Desenhar o bordo ─────────────────────────────────────────────────
        private void DrawBorder()
        {
            if (_zoneBorderRenderer == null) return;
            _zoneBorderRenderer.positionCount = _borderSegments + 1;
            for (int i = 0; i <= _borderSegments; i++)
            {
                float angle = (float)i / _borderSegments * Mathf.PI * 2f;
                float x     = Center.x + Mathf.Cos(angle) * CurrentRadius;
                float y     = Center.y + Mathf.Sin(angle) * CurrentRadius;
                _zoneBorderRenderer.SetPosition(i, new Vector3(x, y, 0));
            }
        }
    }
}
