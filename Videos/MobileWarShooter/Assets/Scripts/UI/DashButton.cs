using UnityEngine;
using UnityEngine.UI;
using UnityEngine.EventSystems;

namespace WarZone
{
    // Botão de dash no lado direito do ecrã
    public class DashButton : MonoBehaviour, IPointerDownHandler
    {
        [SerializeField] private Image _cooldownFill;   // Image (Filled) para mostrar cooldown
        [SerializeField] private float _dashCooldown = 1.2f;

        private float _timer;

        void Update()
        {
            if (_timer > 0f)
            {
                _timer -= Time.deltaTime;
                if (_cooldownFill) _cooldownFill.fillAmount = _timer / _dashCooldown;
            }
            else if (_cooldownFill)
            {
                _cooldownFill.fillAmount = 0f;
            }
        }

        public void OnPointerDown(PointerEventData data)
        {
            if (_timer > 0f) return;
            PlayerController.Instance?.StartDash();
            _timer = _dashCooldown;
        }
    }
}
