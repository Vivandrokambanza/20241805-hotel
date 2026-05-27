using UnityEngine;
using UnityEngine.SceneManagement;

namespace WarZone
{
    public class GameManager : MonoBehaviour
    {
        public static GameManager Instance { get; private set; }

        public int  Score    { get; private set; }
        public int  Coins    { get; private set; }
        public int  Wave     { get; private set; }
        public bool GameOver { get; private set; }

        // Eventos que o HUD e outros sistemas escutam
        public event System.Action<int>  OnScoreChanged;
        public event System.Action<int>  OnCoinsChanged;
        public event System.Action<int>  OnWaveStarted;
        public event System.Action       OnGameOver;
        public event System.Action       OnPlayerRevived;

        private int _interstitialEvery = 3;  // mostra interstitial a cada N waves

        void Awake()
        {
            if (Instance != null) { Destroy(gameObject); return; }
            Instance = this;
            DontDestroyOnLoad(gameObject);
        }

        void Start()
        {
            var save = SaveSystem.Instance.Load();
            Coins = save.totalCoins;
        }

        // ── Pontuação & moedas ────────────────────────────────────────────────
        public void AddScore(int amount)
        {
            Score += amount;
            OnScoreChanged?.Invoke(Score);
        }

        public void AddCoins(int amount)
        {
            Coins += amount;
            OnCoinsChanged?.Invoke(Coins);
        }

        public bool SpendCoins(int amount)
        {
            if (Coins < amount) return false;
            Coins -= amount;
            OnCoinsChanged?.Invoke(Coins);
            return true;
        }

        // ── Ondas ─────────────────────────────────────────────────────────────
        public void StartNextWave()
        {
            Wave++;
            OnWaveStarted?.Invoke(Wave);

            if (Wave % _interstitialEvery == 0)
                AdManager.Instance?.ShowInterstitial();
        }

        // ── Game Over & Revive ────────────────────────────────────────────────
        public void TriggerGameOver()
        {
            if (GameOver) return;
            GameOver = true;
            Time.timeScale = 0f;
            OnGameOver?.Invoke();

            SaveSystem.Instance.Save(new SaveData
            {
                highScore  = Mathf.Max(Score, SaveSystem.Instance.Load().highScore),
                totalCoins = Coins
            });

            // Oferece revive com anúncio
            AdManager.Instance?.ShowReviveOffer(OnReviveAccepted, OnReviveDeclined);
        }

        private void OnReviveAccepted()
        {
            GameOver = false;
            Time.timeScale = 1f;
            PlayerHealth.Instance?.Revive();
            AdManager.Instance?.DoubleCoinRewardActive();
            OnPlayerRevived?.Invoke();
        }

        private void OnReviveDeclined()
        {
            Time.timeScale = 1f;
            UIManager.Instance?.ShowGameOverScreen(Score, Wave);
        }

        // ── Navegação ─────────────────────────────────────────────────────────
        public void RestartGame()
        {
            Time.timeScale = 1f;
            SceneManager.LoadScene("GameScene");
        }

        public void GoToMainMenu()
        {
            Time.timeScale = 1f;
            SceneManager.LoadScene("MainMenu");
        }
    }
}
