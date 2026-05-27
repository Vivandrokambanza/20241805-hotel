using UnityEngine;

namespace WarZone
{
    public class AudioManager : MonoBehaviour
    {
        public static AudioManager Instance { get; private set; }

        [Header("SFX")]
        [SerializeField] private AudioClip _shootClip;
        [SerializeField] private AudioClip _emptyClip;
        [SerializeField] private AudioClip _reloadClip;
        [SerializeField] private AudioClip _hitClip;
        [SerializeField] private AudioClip _deathClip;
        [SerializeField] private AudioClip _pickupClip;
        [SerializeField] private AudioClip _dashClip;
        [SerializeField] private AudioClip _waveStartClip;
        [SerializeField] private AudioClip _waveEndClip;

        [Header("Music")]
        [SerializeField] private AudioClip _gameMusic;
        [SerializeField] private AudioClip _menuMusic;

        [Header("Settings")]
        [Range(0f, 1f)] [SerializeField] private float _sfxVolume  = 0.8f;
        [Range(0f, 1f)] [SerializeField] private float _musicVolume = 0.5f;

        private AudioSource _musicSource;
        private AudioSource _sfxSource;

        void Awake()
        {
            if (Instance != null) { Destroy(gameObject); return; }
            Instance = this;
            DontDestroyOnLoad(gameObject);

            _musicSource = gameObject.AddComponent<AudioSource>();
            _musicSource.loop   = true;
            _musicSource.volume = _musicVolume;

            _sfxSource = gameObject.AddComponent<AudioSource>();
            _sfxSource.volume = _sfxVolume;
        }

        public void PlayShoot()     => _sfxSource.PlayOneShot(_shootClip);
        public void PlayEmpty()     => _sfxSource.PlayOneShot(_emptyClip);
        public void PlayReload()    => _sfxSource.PlayOneShot(_reloadClip);
        public void PlayHit()       => _sfxSource.PlayOneShot(_hitClip);
        public void PlayDeath()     => _sfxSource.PlayOneShot(_deathClip);
        public void PlayPickup()    => _sfxSource.PlayOneShot(_pickupClip);
        public void PlayDash()      => _sfxSource.PlayOneShot(_dashClip);
        public void PlayWaveStart() => _sfxSource.PlayOneShot(_waveStartClip);
        public void PlayWaveEnd()   => _sfxSource.PlayOneShot(_waveEndClip);

        public void PlayGameMusic()
        {
            if (_musicSource.clip == _gameMusic && _musicSource.isPlaying) return;
            _musicSource.clip = _gameMusic;
            _musicSource.Play();
        }

        public void PlayMenuMusic()
        {
            if (_musicSource.clip == _menuMusic && _musicSource.isPlaying) return;
            _musicSource.clip = _menuMusic;
            _musicSource.Play();
        }

        public void SetSFXVolume(float v)   { _sfxVolume   = v; _sfxSource.volume   = v; }
        public void SetMusicVolume(float v) { _musicVolume = v; _musicSource.volume = v; }
    }
}
