using UnityEngine;

namespace WarZone
{
    public class Bullet : MonoBehaviour
    {
        private Vector2 _dir;
        private float   _speed;
        private int     _damage;
        private bool    _fromPlayer;
        private float   _lifetime = 3f;

        public void Init(Vector2 direction, float speed, int damage, bool isPlayerBullet)
        {
            _dir        = direction.normalized;
            _speed      = speed;
            _damage     = damage;
            _fromPlayer = isPlayerBullet;
            Destroy(gameObject, _lifetime);
        }

        void Update()
        {
            transform.Translate(_dir * _speed * Time.deltaTime, Space.World);
        }

        void OnTriggerEnter2D(Collider2D other)
        {
            if (_fromPlayer)
            {
                // Bala do jogador atinge inimigo
                if (other.CompareTag("Enemy"))
                {
                    other.GetComponent<EnemyBase>()?.TakeDamage(_damage);
                    Destroy(gameObject);
                }
                else if (other.CompareTag("Wall"))
                {
                    Destroy(gameObject);
                }
            }
            else
            {
                // Bala do inimigo atinge jogador
                if (other.CompareTag("Player"))
                {
                    other.GetComponent<PlayerHealth>()?.TakeDamage(_damage);
                    Destroy(gameObject);
                }
                else if (other.CompareTag("Wall"))
                {
                    Destroy(gameObject);
                }
            }
        }
    }
}
