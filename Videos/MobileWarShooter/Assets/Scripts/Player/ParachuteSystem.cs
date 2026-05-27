using UnityEngine;
using System.Collections;
using UnityEngine.UI;

namespace WarZone
{
    // Controla o salto do avião e queda de paraquedas — início de cada partida
    public class ParachuteSystem : MonoBehaviour
    {
        [Header("Plane")]
        [SerializeField] private Transform  _plane;
        [SerializeField] private float      _planeSpeed   = 15f;
        [SerializeField] private Vector2    _planeStart   = new(-220, 50);
        [SerializeField] private Vector2    _planeEnd     = new( 220, 50);

        [Header("Parachute Fall")]
        [SerializeField] private float _freeFallSpeed  = 25f;
        [SerializeField] private float _chuteSpeed     = 5f;
        [SerializeField] private float _chuteOpenDelay = 1.5f;  // segundos em queda livre
        [SerializeField] private GameObject _chuteSprite;
        [SerializeField] private Button     _jumpButton;

        [Header("Camera")]
        [SerializeField] private Camera _cam;

        private bool   _jumped      = false;
        private bool   _landed      = false;
        private bool   _chuteOpen   = false;
        private float  _fallTimer   = 0f;
        private float  _groundY     = 0f;

        private Rigidbody2D _rb;
        private PlayerController _pc;

        void Awake()
        {
            _rb = GetComponent<Rigidbody2D>();
            _pc = GetComponent<PlayerController>();
        }

        void Start()
        {
            // Desativa controlos até aterrar
            _pc.enabled = false;
            if (_rb) _rb.simulated = false;

            // Posiciona jogador no avião
            transform.position = _planeStart;
            if (_chuteSprite) _chuteSprite.SetActive(false);

            _jumpButton?.gameObject.SetActive(true);
            _jumpButton?.onClick.AddListener(Jump);

            StartCoroutine(MovePlane());
        }

        void Update()
        {
            if (!_jumped || _landed) return;

            _fallTimer += Time.deltaTime;

            // Abre paraquedas após queda livre
            if (!_chuteOpen && _fallTimer >= _chuteOpenDelay)
            {
                _chuteOpen = true;
                if (_chuteSprite) _chuteSprite.SetActive(true);
                AudioManager.Instance?.PlayChuteOpen();
            }

            float speed = _chuteOpen ? _chuteSpeed : _freeFallSpeed;
            transform.Translate(Vector3.down * speed * Time.deltaTime);

            // Aterrou
            if (transform.position.y <= _groundY)
            {
                Land();
            }
        }

        private IEnumerator MovePlane()
        {
            while (!_jumped && _plane != null)
            {
                _plane.position = Vector3.MoveTowards(
                    _plane.position,
                    (Vector3)_planeEnd,
                    _planeSpeed * Time.deltaTime);

                // Move jogador com o avião
                transform.position = _plane.position;

                // Auto-jump no fim do percurso
                if (Vector2.Distance(_plane.position, _planeEnd) < 1f)
                    Jump();

                yield return null;
            }
        }

        public void Jump()
        {
            if (_jumped) return;
            _jumped = true;
            _jumpButton?.gameObject.SetActive(false);
            AudioManager.Instance?.PlayJump();
        }

        private void Land()
        {
            _landed = true;
            Vector3 pos = transform.position;
            pos.y = _groundY;
            transform.position = pos;

            if (_chuteSprite) _chuteSprite.SetActive(false);
            if (_rb) _rb.simulated = true;

            // Activa controlos do jogador
            _pc.enabled = true;
            GetComponent<PlayerShooter>().enabled = true;

            // Regista no MatchManager
            MatchManager.Instance?.RegisterPlayer(_pc.GetComponent<PlayerController>());

            AudioManager.Instance?.PlayLand();
        }
    }
}
