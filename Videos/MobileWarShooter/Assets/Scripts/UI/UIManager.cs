using UnityEngine;
using UnityEngine.UI;
using TMPro;
using System.Collections;

namespace WarZone
{
    public class UIManager : MonoBehaviour
    {
        public static UIManager Instance { get; private set; }

        [Header("HUD")]
        [SerializeField] private Slider     _hpSlider;
        [SerializeField] private TextMeshProUGUI _hpText;
        [SerializeField] private TextMeshProUGUI _ammoText;
        [SerializeField] private TextMeshProUGUI _coinsText;
        [SerializeField] private TextMeshProUGUI _scoreText;
        [SerializeField] private TextMeshProUGUI _waveText;
        [SerializeField] private Image      _reloadBar;

        [Header("Wave Banner")]
        [SerializeField] private GameObject      _waveBanner;
        [SerializeField] private TextMeshProUGUI _waveBannerText;

        [Header("Wave Complete")]
        [SerializeField] private GameObject      _waveCompletePanel;
        [SerializeField] private TextMeshProUGUI _waveCompleteTxt;

        [Header("Game Over")]
        [SerializeField] private GameObject      _gameOverPanel;
        [SerializeField] private TextMeshProUGUI _finalScoreText;
        [SerializeField] private TextMeshProUGUI _finalWaveText;
        [SerializeField] private TextMeshProUGUI _highScoreText;
        [SerializeField] private Button          _restartBtn;
        [SerializeField] private Button          _menuBtn;

        [Header("Revive")]
        [SerializeField] private GameObject      _revivePanel;
        [SerializeField] private Button          _watchAdBtn;
        [SerializeField] private Button          _declineBtn;

        void Awake()
        {
            if (Instance != null) { Destroy(gameObject); return; }
            Instance = this;
        }

        void Start()
        {
            // Liga eventos do jogador
            if (PlayerHealth.Instance != null)
                PlayerHealth.Instance.OnHpChanged += UpdateHP;

            if (PlayerShooter.Instance != null) { /* ligar ammo */ }

            var gm = GameManager.Instance;
            if (gm != null)
            {
                gm.OnScoreChanged  += v => { if (_scoreText) _scoreText.text = $"{v:N0}"; };
                gm.OnCoinsChanged  += v => { if (_coinsText) _coinsText.text = $"{v}"; };
                gm.OnWaveStarted   += v => { if (_waveText)  _waveText.text  = $"Wave {v}"; };
            }

            _restartBtn?.onClick.AddListener(() => GameManager.Instance?.RestartGame());
            _menuBtn?.onClick.AddListener(()    => GameManager.Instance?.GoToMainMenu());
        }

        private void UpdateHP(int current, int max)
        {
            if (_hpSlider) _hpSlider.value = (float)current / max;
            if (_hpText)   _hpText.text    = $"{current} / {max}";
        }

        public void UpdateAmmo(int current, int mag, bool reloading)
        {
            if (_ammoText)
                _ammoText.text = reloading ? "RECARGA..." : $"{current} / {mag}";
        }

        // ── Banners ───────────────────────────────────────────────────────────
        public void ShowWaveBanner(int wave)
        {
            if (_waveBanner == null) return;
            if (_waveBannerText) _waveBannerText.text = $"WAVE {wave}";
            StartCoroutine(ShowThenHide(_waveBanner, 2f));
        }

        public void ShowWaveCompletePanel(int wave)
        {
            if (_waveCompletePanel == null) return;
            if (_waveCompleteTxt) _waveCompleteTxt.text = $"Wave {wave} completa!";
            StartCoroutine(ShowThenHide(_waveCompletePanel, 2.5f));
        }

        // ── Game Over ─────────────────────────────────────────────────────────
        public void ShowGameOverScreen(int score, int wave)
        {
            if (_gameOverPanel == null) return;
            _gameOverPanel.SetActive(true);
            if (_finalScoreText) _finalScoreText.text = $"Score: {score:N0}";
            if (_finalWaveText)  _finalWaveText.text  = $"Wave: {wave}";
            var save = SaveSystem.Instance?.Load();
            if (_highScoreText && save != null)
                _highScoreText.text = $"HighScore: {save.highScore:N0}";
        }

        // ── Revive ────────────────────────────────────────────────────────────
        public void ShowRevivePanel(System.Action onAccept, System.Action onDecline)
        {
            if (_revivePanel == null) { onDecline?.Invoke(); return; }
            _revivePanel.SetActive(true);
            _watchAdBtn?.onClick.RemoveAllListeners();
            _declineBtn?.onClick.RemoveAllListeners();
            _watchAdBtn?.onClick.AddListener(() => { _revivePanel.SetActive(false); onAccept?.Invoke(); });
            _declineBtn?.onClick.AddListener(() => { _revivePanel.SetActive(false); onDecline?.Invoke(); });
        }

        private IEnumerator ShowThenHide(GameObject obj, float delay)
        {
            obj.SetActive(true);
            yield return new WaitForSeconds(delay);
            obj.SetActive(false);
        }
    }
}
