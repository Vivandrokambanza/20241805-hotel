using UnityEngine;
using UnityEngine.UI;

namespace WarZone
{
    // Minimapa circular no canto superior direito
    public class MiniMap : MonoBehaviour
    {
        [SerializeField] private RectTransform _mapContainer;
        [SerializeField] private RawImage      _mapImage;
        [SerializeField] private RectTransform _playerDot;
        [SerializeField] private float         _worldScale = 200f;  // metade do tamanho do mundo
        [SerializeField] private float         _mapSize    = 100f;  // px da minimap

        [Header("Dots de Inimigos")]
        [SerializeField] private GameObject    _enemyDotPrefab;

        private Transform _playerTransform;

        void Start()
        {
            _playerTransform = PlayerController.Instance?.transform;
        }

        void LateUpdate()
        {
            if (_playerTransform == null) return;
            UpdatePlayerDot();
        }

        private void UpdatePlayerDot()
        {
            Vector2 worldPos   = _playerTransform.position;
            Vector2 normalised = worldPos / _worldScale;          // -1 a 1
            Vector2 mapPos     = normalised * (_mapSize * 0.5f);   // pixels

            _playerDot.anchoredPosition = mapPos;
        }

        // Converte posição mundo para posição no minimap
        public Vector2 WorldToMap(Vector2 world)
        {
            Vector2 n = world / _worldScale;
            return n * (_mapSize * 0.5f);
        }
    }
}
