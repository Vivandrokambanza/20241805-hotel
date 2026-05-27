using UnityEngine;
using UnityEngine.EventSystems;
using UnityEngine.UI;

namespace WarZone
{
    // Arrasta para uma imagem de background circular na Canvas
    public class VirtualJoystick : MonoBehaviour, IPointerDownHandler, IDragHandler, IPointerUpHandler
    {
        [SerializeField] private RectTransform _background;
        [SerializeField] private RectTransform _handle;
        [SerializeField] private float         _handleRange = 75f;  // px máximo de deslocamento

        private Canvas _canvas;
        private Camera _cam;

        public Vector2 Direction { get; private set; }

        void Awake()
        {
            _canvas = GetComponentInParent<Canvas>();
            _cam    = _canvas.renderMode == RenderMode.ScreenSpaceCamera ? _canvas.worldCamera : null;
        }

        public void OnPointerDown(PointerEventData data)
        {
            // Move o background para onde o utilizador tocou
            _background.anchoredPosition = ScreenToAnchoredPos(data.position);
            MoveHandle(data.position);
        }

        public void OnDrag(PointerEventData data) => MoveHandle(data.position);

        public void OnPointerUp(PointerEventData data)
        {
            Direction              = Vector2.zero;
            _handle.anchoredPosition = Vector2.zero;
        }

        private void MoveHandle(Vector2 screenPos)
        {
            RectTransformUtility.ScreenPointToLocalPointInRectangle(
                _background, screenPos, _cam, out Vector2 local);

            Vector2 clamped = Vector2.ClampMagnitude(local, _handleRange);
            _handle.anchoredPosition = clamped;
            Direction = clamped.magnitude > 5f ? clamped / _handleRange : Vector2.zero;
        }

        private Vector2 ScreenToAnchoredPos(Vector2 screenPos)
        {
            var parent = _background.parent as RectTransform;
            RectTransformUtility.ScreenPointToLocalPointInRectangle(
                parent, screenPos, _cam, out Vector2 local);
            return local;
        }
    }
}
