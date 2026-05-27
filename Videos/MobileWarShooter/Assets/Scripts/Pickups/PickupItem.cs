using UnityEngine;

namespace WarZone
{
    public enum PickupType { Coin, HealthPack, AmmoPack }

    public class PickupItem : MonoBehaviour
    {
        [SerializeField] private PickupType _type  = PickupType.Coin;
        [SerializeField] private int        _value = 1;
        [SerializeField] private float      _lifetime = 8f;   // desaparece após N segundos
        [SerializeField] private float      _bobSpeed = 2f;
        [SerializeField] private float      _bobHeight = 0.15f;

        private Vector3 _startPos;

        void Start()
        {
            _startPos = transform.position;
            Destroy(gameObject, _lifetime);
        }

        void Update()
        {
            // Animação de flutuação
            float y = Mathf.Sin(Time.time * _bobSpeed) * _bobHeight;
            transform.position = _startPos + new Vector3(0, y, 0);
        }

        void OnTriggerEnter2D(Collider2D other)
        {
            if (!other.CompareTag("Player")) return;

            switch (_type)
            {
                case PickupType.Coin:
                    GameManager.Instance?.AddCoins(_value);
                    break;
                case PickupType.HealthPack:
                    PlayerHealth.Instance?.Heal(_value);
                    break;
                case PickupType.AmmoPack:
                    // PlayerShooter handles ammo internally on reload; just notify
                    break;
            }

            AudioManager.Instance?.PlayPickup();
            Destroy(gameObject);
        }
    }
}
