# War Zone — Top-Down Shooter (Archero Style)
## Guia Completo de Setup no Unity

---

## 1. INSTALAR O UNITY

1. Vai a https://unity.com/download
2. Instala o **Unity Hub**
3. No Unity Hub → Installs → Add → **Unity 2022.3 LTS**
4. Adiciona o módulo: **Android Build Support**

---

## 2. CRIAR O PROJETO

1. Unity Hub → New Project → **2D (URP)**
2. Nome: `MobileWarShooter`
3. Copia a pasta `Assets/Scripts/` para o projeto

---

## 3. ESTRUTURA DE CENAS

Cria 2 cenas:
- `MainMenu` (cena 0)
- `GameScene` (cena 1)

**File > Build Settings > adiciona ambas por esta ordem**

---

## 4. GAMEOBJECTS NECESSÁRIOS NA GAMESCENE

### Manager Objects (GameObject vazio com scripts)
```
_Managers (vazio)
  ├── GameManager.cs
  ├── MatchManager.cs  ← REMOVER se não for BR
  ├── SaveSystem.cs
  ├── AudioManager.cs
  ├── UpgradeSystem.cs
  ├── PlayerStats.cs (no mesmo GO do Player)
  └── WaveManager.cs

_Map (vazio)
  └── LootManager.cs

Camera (Main Camera)
  └── CameraFollow.cs → arrasta o Player para o campo Target
```

### Player
```
Player (Sprite)
  ├── PlayerController.cs  → arrasta o VirtualJoystick para o campo
  ├── PlayerHealth.cs
  ├── PlayerShooter.cs     → arrasta o WeaponData (ScriptableObject)
  ├── PlayerStats.cs
  ├── PlayerInventory.cs
  ├── Rigidbody2D          → Body Type = Dynamic, Constraints = Freeze Rotation Z
  ├── CircleCollider2D     → Tag = "Player"
  └── Muzzle (GameObject filho vazio — é daqui que sai a bala)
```

### Canvas (UI)
```
Canvas (Screen Space - Overlay)
  ├── VirtualJoystick → background + handle
  ├── DashButton
  ├── HUD (HP, coins, wave, ammo)
  ├── UpgradePanel (desativado por padrão)
  ├── GameOverPanel (desativado)
  ├── VictoryPanel (desativado)
  └── MiniMap
```

---

## 5. WEAPON DATA (ScriptableObjects)

Para cada arma: botão direito em Project → Create → WarZone → WeaponData

| Arma    | Damage | FireRate | Magazine | ReloadTime |
|---------|--------|----------|----------|------------|
| Pistola | 15     | 2.5      | 12       | 1.5s       |
| M4A1    | 25     | 4.0      | 30       | 1.8s       |
| Shotgun | 45     | 1.2      | 8        | 2.0s       |
| AWM     | 90     | 0.8      | 5        | 2.5s       |
| MP40    | 18     | 6.0      | 35       | 1.5s       |

---

## 6. ENEMY PREFABS

Cria 3 prefabs de inimigo:
- **Infantry**: HP=60, Speed=2.5, Damage=10, coins=3
- **Heavy**:    HP=150, Speed=1.5, Damage=20, coins=8
- **Sniper**:   HP=80, Speed=2.0, Damage=35 (projectile), coins=10

Cada um tem:
- SpriteRenderer + tag "Enemy"
- Rigidbody2D (Dynamic)
- CircleCollider2D (Is Trigger = ON)
- EnemyAI.cs

---

## 7. WAVE MANAGER

No WaveManager, arrasta os prefabs:
- `_infantryPrefab` → Infantry
- `_heavyPrefab`    → Heavy
- `_sniperPrefab`   → Sniper

Boss a cada 5 vagas: cria prefab com `BossEnemy.cs` e arrasta para WaveManager.

---

## 8. MONETIZAÇÃO (Unity Ads)

1. Window → Package Manager → Unity Registry → **Advertisement Legacy**
2. Vai a https://unity.com → Dashboard → Criar projeto
3. Copia os Game IDs para o `AdManager.cs`
4. Descomentar as linhas do AdManager (indicadas com `//`)
5. **testMode = false** antes de publicar

### Anúncios configurados:
| Tipo         | Quando aparece                  | Receita |
|---|---|---|
| Rewarded     | Oferta de revive após morte     | Alta    |
| Rewarded     | 2x coins no fim da onda         | Alta    |
| Interstitial | A cada 3 vagas                  | Média   |

---

## 9. PUBLICAR NO GOOGLE PLAY

1. Unity → File → Build Settings → Android
2. Player Settings → Package Name: `com.SEUNOME.warzone`
3. Keystore: gera uma com `Keystore Manager`
4. Build → `warzone.aab`
5. Google Play Console ($25 taxa única)
6. Upload o `.aab` → Publicar em Beta primeiro

---

## 10. ESTRUTURA FINAL DE SCRIPTS

```
Assets/Scripts/
├── Core/
│   ├── GameManager.cs       ← score, coins, game state
│   ├── SaveSystem.cs        ← guarda progresso
│   ├── AudioManager.cs      ← todos os sons
│   ├── PlayerStats.cs       ← stats do jogador
│   ├── UpgradeSystem.cs     ← cartas de upgrade entre vagas ⭐
│   └── CameraFollow.cs      ← câmara suave
├── Player/
│   ├── PlayerController.cs  ← movimento joystick + dash
│   ├── PlayerHealth.cs      ← HP, escudo, morte
│   ├── PlayerShooter.cs     ← auto-mira, disparo automático
│   └── PlayerInventory.cs   ← 2 slots de arma
├── Enemy/
│   ├── EnemyBase.cs         ← base de todos os inimigos
│   ├── EnemyAI.cs           ← IA (perseguir, atirar)
│   ├── WaveManager.cs       ← vagas infinitas
│   └── BossEnemy.cs         ← boss a cada 5 vagas ⭐
├── Weapons/
│   ├── WeaponData.cs        ← ScriptableObject com stats
│   └── Bullet.cs            ← movimento + colisão
├── UI/
│   ├── VirtualJoystick.cs   ← joystick táctil
│   ├── DashButton.cs        ← botão de dash com cooldown
│   ├── UIManager.cs         ← HUD completo
│   ├── DamageNumbers.cs     ← números flutuantes
│   └── MiniMap.cs           ← minimap
├── Monetization/
│   └── AdManager.cs         ← Unity Ads (rewarded + interstitial)
└── Pickups/
    └── PickupItem.cs        ← moedas, kits, munição
```

---

## RECEITA ESTIMADA (com 1000 downloads/dia)

| Fonte                  | €/dia   | €/mês   |
|------------------------|---------|---------|
| Rewarded ads (revive)  | €5-15   | €150-450|
| Interstitial (vagas)   | €3-8    | €90-240 |
| IAP coins pack         | €2-10   | €60-300 |
| **TOTAL**              |         | **€300-1000** |

Com 10.000 downloads/dia (crescimento orgânico): **€3.000-10.000/mês**
