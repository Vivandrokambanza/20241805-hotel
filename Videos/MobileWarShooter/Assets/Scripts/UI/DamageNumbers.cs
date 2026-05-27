using UnityEngine;
using TMPro;
using System.Collections;

namespace WarZone
{
    // Números de dano flutuantes — feedback visual essencial
    public class DamageNumbers : MonoBehaviour
    {
        [SerializeField] private TextMeshPro _text;
        [SerializeField] private float _riseSpeed  = 2f;
        [SerializeField] private float _lifetime   = 0.8f;
        [SerializeField] private AnimationCurve _scaleCurve;

        private Color _color;

        public static void Spawn(Vector3 pos, int damage, bool isCrit = false, bool isHeal = false)
        {
            // Prefab deve existir em Resources/DamageNumber
            var prefab = Resources.Load<GameObject>("DamageNumber");
            if (prefab == null) return;

            var go = Instantiate(prefab,
                pos + new Vector3(Random.Range(-0.3f, 0.3f), 0.5f, 0),
                Quaternion.identity);

            go.GetComponent<DamageNumbers>()?.Init(damage, isCrit, isHeal);
        }

        public void Init(int value, bool isCrit, bool isHeal)
        {
            if (_text == null) _text = GetComponent<TextMeshPro>();

            if (isHeal)
            {
                _text.text  = $"+{value}";
                _text.color = Color.green;
            }
            else if (isCrit)
            {
                _text.text  = $"{value}!";
                _text.color = new Color(1f, 0.5f, 0f);
                _text.fontSize *= 1.4f;
            }
            else
            {
                _text.text  = value.ToString();
                _text.color = Color.white;
            }

            _color = _text.color;
            StartCoroutine(Animate());
        }

        private IEnumerator Animate()
        {
            float t = 0f;
            Vector3 start = transform.position;

            while (t < _lifetime)
            {
                t += Time.deltaTime;
                float pct = t / _lifetime;

                transform.position = start + Vector3.up * _riseSpeed * t;

                // Fade out no final
                _color.a   = 1f - pct;
                _text.color = _color;

                yield return null;
            }
            Destroy(gameObject);
        }
    }
}
