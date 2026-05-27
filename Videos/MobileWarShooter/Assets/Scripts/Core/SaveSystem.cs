using UnityEngine;

namespace WarZone
{
    [System.Serializable]
    public class SaveData
    {
        public int  highScore;
        public int  totalCoins;
        public int  weaponLevel;
        public int  armorLevel;
        public bool noAds;
    }

    public class SaveSystem : MonoBehaviour
    {
        public static SaveSystem Instance { get; private set; }

        private const string SaveKey = "WarZone_Save";

        void Awake()
        {
            if (Instance != null) { Destroy(gameObject); return; }
            Instance = this;
            DontDestroyOnLoad(gameObject);
        }

        public void Save(SaveData data)
        {
            string json = JsonUtility.ToJson(data);
            PlayerPrefs.SetString(SaveKey, json);
            PlayerPrefs.Save();
        }

        public SaveData Load()
        {
            if (!PlayerPrefs.HasKey(SaveKey)) return new SaveData();
            return JsonUtility.FromJson<SaveData>(PlayerPrefs.GetString(SaveKey))
                   ?? new SaveData();
        }

        public void DeleteAll()
        {
            PlayerPrefs.DeleteKey(SaveKey);
            PlayerPrefs.Save();
        }
    }
}
