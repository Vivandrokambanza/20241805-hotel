using UnityEngine;
using System.Collections.Generic;

namespace WarZone
{
    // ════════════════════════════════════════════════════════════
    //  UPGRADE SYSTEM  —  a mecânica core que torna o jogo viciante
    //  Após cada vaga: pausa o jogo e mostra 3 cartas aleatórias
    // ════════════════════════════════════════════════════════════

    [System.Serializable]
    public class UpgradeCard
    {
        public UpgradeType  Type;
        public string       Title;
        public string       Description;
        public Sprite       Icon;
        public Color        CardColor = Color.white;
        public int          Weight = 10;   // probabilidade relativa de aparecer
    }

    public class UpgradeSystem : MonoBehaviour
    {
        public static UpgradeSystem Instance { get; private set; }

        [Header("Todas as cartas possíveis (preenche no Inspector)")]
        [SerializeField] private List<UpgradeCard> _allCards = new();

        [Header("Quantas cartas mostrar por vez")]
        [SerializeField] private int _cardsToShow = 3;

        private bool _waitingForChoice;

        public event System.Action<List<UpgradeCard>> OnShowUpgrades;

        void Awake()
        {
            if (Instance != null) { Destroy(gameObject); return; }
            Instance = this;
            BuildDefaultCards();
        }

        // ── Chamado pelo WaveManager quando uma vaga termina ─────────────────
        public void ShowUpgradeScreen()
        {
            if (_waitingForChoice) return;
            _waitingForChoice = true;

            Time.timeScale = 0f;    // pausa o jogo

            var cards = PickRandomCards(_cardsToShow);
            OnShowUpgrades?.Invoke(cards);
            UIManager.Instance?.ShowUpgradePanel(cards, OnCardChosen);
        }

        private void OnCardChosen(UpgradeCard card)
        {
            _waitingForChoice = false;
            Time.timeScale = 1f;    // retoma o jogo

            PlayerStats.Instance?.ApplyUpgrade(card.Type);
            UIManager.Instance?.HideUpgradePanel();

            // Feedback visual
            AudioManager.Instance?.PlayUpgradeSound();
            GameManager.Instance?.AddScore(50);
        }

        // ── Seleccionar N cartas aleatórias com peso ─────────────────────────
        private List<UpgradeCard> PickRandomCards(int count)
        {
            var pool      = new List<UpgradeCard>(_allCards);
            var chosen    = new List<UpgradeCard>();
            int totalWeight = 0;
            foreach (var c in pool) totalWeight += c.Weight;

            for (int i = 0; i < count && pool.Count > 0; i++)
            {
                int roll    = Random.Range(0, totalWeight);
                int running = 0;
                for (int j = 0; j < pool.Count; j++)
                {
                    running += pool[j].Weight;
                    if (roll < running)
                    {
                        chosen.Add(pool[j]);
                        totalWeight -= pool[j].Weight;
                        pool.RemoveAt(j);
                        break;
                    }
                }
            }
            return chosen;
        }

        // ── Cartas padrão (podes editar no Inspector) ─────────────────────────
        private void BuildDefaultCards()
        {
            if (_allCards.Count > 0) return;   // já definidas no Inspector

            _allCards = new List<UpgradeCard>
            {
                new() { Type = UpgradeType.Damage,          Title = "Bala Perfurante",   Description = "+20% dano em todos os tiros",     CardColor = new Color(1f, 0.4f, 0.4f), Weight = 10 },
                new() { Type = UpgradeType.FireRate,         Title = "Cadência Rápida",   Description = "+15% velocidade de disparo",      CardColor = new Color(1f, 0.8f, 0.2f), Weight = 10 },
                new() { Type = UpgradeType.Speed,            Title = "Botas Militares",   Description = "+15% velocidade de movimento",    CardColor = new Color(0.4f, 0.8f, 1f), Weight = 10 },
                new() { Type = UpgradeType.MaxHP,            Title = "Colete Reforçado",  Description = "+30 HP máximo",                   CardColor = new Color(0.4f, 1f, 0.4f), Weight = 10 },
                new() { Type = UpgradeType.HealNow,          Title = "Kit Médico",        Description = "Recupera 40 HP agora",            CardColor = new Color(0.2f, 1f, 0.5f), Weight = 8  },
                new() { Type = UpgradeType.BulletRange,      Title = "Luneta",            Description = "+2 de alcance das balas",         CardColor = new Color(0.8f, 0.4f, 1f), Weight = 8  },
                new() { Type = UpgradeType.PiercingBullets,  Title = "Bala AP",           Description = "Balas atravessam inimigos",       CardColor = new Color(1f, 0.5f, 0f),   Weight = 5  },
                new() { Type = UpgradeType.DoubleShot,       Title = "Duplo Cano",        Description = "Dispara 2 balas de uma vez",      CardColor = new Color(1f, 0.3f, 0.8f), Weight = 5  },
                new() { Type = UpgradeType.ExplosiveBullets, Title = "Bala Explosiva",    Description = "Balas explodem no impacto",       CardColor = new Color(1f, 0.5f, 0f),   Weight = 4  },
                new() { Type = UpgradeType.Ricochet,         Title = "Ricochet",          Description = "Balas ricocheteiam 1 vez",        CardColor = new Color(0.5f, 0.5f, 1f), Weight = 4  },
                new() { Type = UpgradeType.Shield,           Title = "Escudo Pessoal",    Description = "Absorve 1 golpe sem dano",        CardColor = new Color(0.2f, 0.8f, 1f), Weight = 5  },
            };
        }
    }
}
