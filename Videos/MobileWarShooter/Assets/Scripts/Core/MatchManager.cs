using UnityEngine;
using System.Collections;
using System.Collections.Generic;

namespace WarZone
{
    public enum MatchState { WaitingToStart, Parachuting, Playing, Finished }

    // Gere o estado global da partida Battle Royale
    public class MatchManager : MonoBehaviour
    {
        public static MatchManager Instance { get; private set; }

        [Header("Match Settings")]
        [SerializeField] private int   _totalPlayers    = 20;   // 1 jogador + 19 bots
        [SerializeField] private float _matchStartDelay = 3f;
        [SerializeField] private float _planeFlightTime = 25f;  // tempo de voo do avião

        public MatchState State          { get; private set; } = MatchState.WaitingToStart;
        public int        PlayersAlive   { get; private set; }
        public int        TotalPlayers   => _totalPlayers;
        public float      MatchTime      { get; private set; }
        public bool       PlayerWon      { get; private set; }

        private List<IMatchPlayer> _alivePlayers = new();

        public event System.Action<int>        OnPlayersAliveChanged;
        public event System.Action<MatchState> OnStateChanged;
        public event System.Action             OnMatchEnd;

        void Awake()
        {
            if (Instance != null) { Destroy(gameObject); return; }
            Instance = this;
        }

        void Start() => StartCoroutine(RunMatch());

        void Update()
        {
            if (State == MatchState.Playing) MatchTime += Time.deltaTime;
        }

        // ── Fluxo da partida ─────────────────────────────────────────────────
        private IEnumerator RunMatch()
        {
            SetState(MatchState.WaitingToStart);
            UIManager.Instance?.ShowCountdown(_matchStartDelay);
            yield return new WaitForSeconds(_matchStartDelay);

            SetState(MatchState.Parachuting);
            AudioManager.Instance?.PlayGameMusic();
            yield return new WaitForSeconds(_planeFlightTime);

            SetState(MatchState.Playing);
            SafeZone.Instance?.StartShrinking();
        }

        private void SetState(MatchState s)
        {
            State = s;
            OnStateChanged?.Invoke(s);
        }

        // ── Registo de jogadores ─────────────────────────────────────────────
        public void RegisterPlayer(IMatchPlayer p)
        {
            _alivePlayers.Add(p);
            PlayersAlive = _alivePlayers.Count;
            OnPlayersAliveChanged?.Invoke(PlayersAlive);
        }

        public void PlayerEliminated(IMatchPlayer p, bool wasHuman)
        {
            _alivePlayers.Remove(p);
            PlayersAlive = _alivePlayers.Count;
            OnPlayersAliveChanged?.Invoke(PlayersAlive);

            if (wasHuman)
            {
                // Jogador morreu — mostra posição final e opção de revive
                GameManager.Instance?.TriggerGameOver();
                return;
            }

            CheckMatchEnd();
        }

        private void CheckMatchEnd()
        {
            if (PlayersAlive > 1) return;

            PlayerWon = _alivePlayers.Count == 1 &&
                        _alivePlayers[0] is PlayerController;

            SetState(MatchState.Finished);
            Time.timeScale = 0.3f;
            StartCoroutine(ShowEndDelay());
            OnMatchEnd?.Invoke();
        }

        private IEnumerator ShowEndDelay()
        {
            yield return new WaitForSecondsRealtime(1.5f);
            Time.timeScale = 1f;
            if (PlayerWon)
                UIManager.Instance?.ShowVictoryScreen();
            else
                UIManager.Instance?.ShowGameOverScreen(
                    GameManager.Instance?.Score ?? 0,
                    _totalPlayers - PlayersAlive);
        }
    }

    // Interface comum a jogador e bots
    public interface IMatchPlayer
    {
        string PlayerName { get; }
        bool   IsAlive    { get; }
    }
}
