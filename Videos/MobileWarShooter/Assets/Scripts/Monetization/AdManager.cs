using UnityEngine;
// Instala Unity Ads via Package Manager: Window > Package Manager > Unity Registry > Advertisement Legacy
// Descomenta as linhas abaixo depois de instalar o pacote
// using UnityEngine.Advertisements;

namespace WarZone
{
    public class AdManager : MonoBehaviour /*, IUnityAdsInitializationListener, IUnityAdsLoadListener, IUnityAdsShowListener */
    {
        public static AdManager Instance { get; private set; }

        [Header("IDs do Unity Ads (preenche após criar conta em unity.com/solutions/ads)")]
        [SerializeField] private string _androidGameId = "YOUR_ANDROID_GAME_ID";
        [SerializeField] private string _iosGameId     = "YOUR_IOS_GAME_ID";
        [SerializeField] private bool   _testMode      = true;   // FALSE antes de publicar

        private const string RewardedId     = "Rewarded_Android";
        private const string InterstitialId = "Interstitial_Android";

        private System.Action _onReviveAccepted;
        private System.Action _onReviveDeclined;
        private bool _doubleCoinActive;

        void Awake()
        {
            if (Instance != null) { Destroy(gameObject); return; }
            Instance = this;
            DontDestroyOnLoad(gameObject);
            InitAds();
        }

        private void InitAds()
        {
            // Advertisement.Initialize(
            //     Application.platform == RuntimePlatform.IPhonePlayer ? _iosGameId : _androidGameId,
            //     _testMode, this);
            Debug.Log("[AdManager] Unity Ads desativado — instala o pacote e descomenta o código.");
        }

        // ── Rewarded (revive / 2x coins) ─────────────────────────────────────
        public void ShowReviveOffer(System.Action onAccept, System.Action onDecline)
        {
            _onReviveAccepted = onAccept;
            _onReviveDeclined = onDecline;

            // Se o anúncio não estiver carregado, mostra painel de escolha via UI
            UIManager.Instance?.ShowRevivePanel(onAccept, onDecline);

            // Com Unity Ads:
            // if (Advertisement.IsReady(RewardedId))
            //     Advertisement.Show(RewardedId, this);
            // else
            //     UIManager.Instance?.ShowRevivePanel(onAccept, onDecline);
        }

        public void ShowInterstitial()
        {
            // Advertisement.Show(InterstitialId, this);
            Debug.Log("[AdManager] Interstitial mostraria aqui.");
        }

        public void DoubleCoinRewardActive() => _doubleCoinActive = true;

        public int ApplyCoinMultiplier(int coins) =>
            _doubleCoinActive ? coins * 2 : coins;

        // ── Unity Ads Callbacks (descomenta depois de instalar o pacote) ──────
        // public void OnInitializationComplete()
        // {
        //     Advertisement.Load(RewardedId, this);
        //     Advertisement.Load(InterstitialId, this);
        // }
        // public void OnInitializationFailed(UnityAdsInitializationError error, string msg)
        //     => Debug.LogError($"Ads init failed: {error} - {msg}");
        //
        // public void OnUnityAdsAdLoaded(string id)   => Debug.Log($"Ad loaded: {id}");
        // public void OnUnityAdsFailedToLoad(string id, UnityAdsLoadError err, string msg)
        //     => Debug.LogWarning($"Ad load failed: {id}");
        //
        // public void OnUnityAdsShowComplete(string id, UnityAdsShowCompletionState state)
        // {
        //     if (id == RewardedId && state == UnityAdsShowCompletionState.COMPLETED)
        //         _onReviveAccepted?.Invoke();
        //     else
        //         _onReviveDeclined?.Invoke();
        //     Advertisement.Load(id, this);  // re-carrega para a próxima vez
        // }
        // public void OnUnityAdsShowFailure(string id, UnityAdsShowError err, string msg)
        //     => _onReviveDeclined?.Invoke();
        // public void OnUnityAdsShowStart(string id)  { }
        // public void OnUnityAdsShowClick(string id)  { }
    }
}
