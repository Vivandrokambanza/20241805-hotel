using UnityEngine;

namespace WarZone
{
    // Câmara suave que segue o jogador — essencial para qualquer jogo top-down
    public class CameraFollow : MonoBehaviour
    {
        [SerializeField] private Transform _target;
        [SerializeField] private float     _smoothSpeed = 6f;
        [SerializeField] private Vector3   _offset      = new(0, 0, -10f);

        [Header("Limites do mapa (deixar 0 para sem limites)")]
        [SerializeField] private float _minX = -195f;
        [SerializeField] private float _maxX =  195f;
        [SerializeField] private float _minY = -195f;
        [SerializeField] private float _maxY =  195f;
        [SerializeField] private bool  _clampToMap = true;

        [Header("Screen Shake")]
        private float _shakeTimer;
        private float _shakeMagnitude;

        void LateUpdate()
        {
            if (_target == null) return;

            Vector3 desired = _target.position + _offset;

            // Shake de ecrã
            if (_shakeTimer > 0f)
            {
                desired += (Vector3)Random.insideUnitCircle * _shakeMagnitude;
                _shakeTimer -= Time.deltaTime;
            }

            Vector3 smoothed = Vector3.Lerp(transform.position, desired, _smoothSpeed * Time.deltaTime);

            // Clamp aos limites do mapa
            if (_clampToMap)
            {
                smoothed.x = Mathf.Clamp(smoothed.x, _minX, _maxX);
                smoothed.y = Mathf.Clamp(smoothed.y, _minY, _maxY);
            }

            smoothed.z = _offset.z;
            transform.position = smoothed;
        }

        // Chamar quando o jogador toma dano ou morre
        public void Shake(float duration = 0.2f, float magnitude = 0.3f)
        {
            _shakeTimer    = duration;
            _shakeMagnitude = magnitude;
        }

        public void SetTarget(Transform t) => _target = t;

        // Singleton opcional para acesso fácil
        public static CameraFollow Instance { get; private set; }
        void Awake() { if (Instance == null) Instance = this; }
    }
}
