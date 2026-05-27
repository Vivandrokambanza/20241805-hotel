using System.Runtime.InteropServices;
using System.Text;

namespace ShooterGame;

// Gera sons WAV em memória e toca via winmm.dll (sem dependências externas)
public sealed class SoundSystem
{
    [DllImport("winmm.dll", SetLastError = true)]
    private static extern bool PlaySound(
        [MarshalAs(UnmanagedType.LPArray)] byte[] pszSound,
        IntPtr hmod, uint fdwSound);

    private const uint SND_MEMORY    = 0x00000004;
    private const uint SND_ASYNC     = 0x00000001;
    private const uint SND_NODEFAULT = 0x00000002;

    private const int SR = 22050; // sample rate

    private readonly byte[] _gunshot;
    private readonly byte[] _empty;
    private readonly byte[] _reload;
    private readonly byte[] _pickup;
    private readonly byte[] _damage;
    private readonly byte[] _death;
    private readonly byte[] _poison;
    private readonly byte[] _footstep;

    public SoundSystem()
    {
        _gunshot  = Build(Gunshot,   0.12);
        _empty    = Build(EmptyClick,0.05);
        _reload   = Build(Reload,    0.70);
        _pickup   = Build(Pickup,    0.30);
        _damage   = Build(Damage,    0.18);
        _death    = Build(Death,     1.20);
        _poison   = Build(Poison,    0.45);
        _footstep = Build(Footstep,  0.06);
    }

    // ── Geradores de amostras ────────────────────────────────────────────────

    private static double Gunshot(double t)
    {
        // Crack de tiro: ruído branco com envelope de ataque rápido
        double env   = Math.Exp(-t * 45);
        double noise = (Random.Shared.NextDouble() * 2 - 1);
        double thud  = Math.Sin(2 * Math.PI * 90 * t) * Math.Exp(-t * 25);
        double snap  = Math.Sin(2 * Math.PI * 1200 * t) * Math.Exp(-t * 80);
        return (noise * 0.55 + thud * 0.35 + snap * 0.25) * env;
    }

    private static double EmptyClick(double t)
    {
        double c1 = Math.Sin(2 * Math.PI * 700 * t) * Math.Exp(-t * 200);
        double c2 = Math.Sin(2 * Math.PI * 400 * t) * Math.Exp(-t * 150);
        return (c1 + c2) * 0.6;
    }

    private static double Reload(double t)
    {
        // Magazine saindo (t≈0.1), scrape metálico (0.2-0.5), magazine entrando (t≈0.58)
        double click1  = Math.Sin(2 * Math.PI * 500 * t) * Math.Exp(-Math.Abs(t - 0.10) * 400);
        double scrape  = (Random.Shared.NextDouble() * 2 - 1)
                         * 0.4 * Math.Exp(-Math.Pow((t - 0.35) / 0.12, 2));
        double metal   = Math.Sin(2 * Math.PI * 250 * t) * Math.Exp(-Math.Pow((t - 0.35) / 0.15, 2)) * 0.5;
        double click2  = Math.Sin(2 * Math.PI * 650 * t) * Math.Exp(-Math.Abs(t - 0.58) * 350);
        double charging= Math.Sin(2 * Math.PI * 400 * t) * Math.Exp(-Math.Abs(t - 0.65) * 300) * 0.7;
        return (click1 + scrape + metal + click2 + charging) * 0.85;
    }

    private static double Pickup(double t)
    {
        // Ding: notas C5→E5→G5
        double n1 = Math.Sin(2 * Math.PI * 523 * t) * GaussEnv(t, 0.04, 40);
        double n2 = Math.Sin(2 * Math.PI * 659 * t) * GaussEnv(t, 0.13, 40);
        double n3 = Math.Sin(2 * Math.PI * 784 * t) * GaussEnv(t, 0.22, 40);
        return (n1 + n2 + n3) * 0.45;
    }

    private static double Damage(double t)
    {
        double thud  = Math.Sin(2 * Math.PI * 55 * t) * Math.Exp(-t * 25);
        double crack = (Random.Shared.NextDouble() * 2 - 1) * Math.Exp(-t * 40) * 0.35;
        return (thud + crack) * 0.9;
    }

    private static double Death(double t)
    {
        // Sweep descendente + respiração pesada
        double freq = 350 * Math.Exp(-t * 1.8);
        double tone = Math.Sin(2 * Math.PI * freq * t) * Math.Exp(-t * 1.5);
        double low  = Math.Sin(2 * Math.PI * 60 * t) * Math.Exp(-t * 0.8) * 0.4;
        double noise= (Random.Shared.NextDouble() * 2 - 1) * Math.Exp(-t * 4) * 0.15;
        return (tone + low + noise) * 0.8;
    }

    private static double Poison(double t)
    {
        // Notas descentes sombrias
        double n1 = Math.Sin(2 * Math.PI * 280 * t) * GaussEnv(t, 0.08, 25);
        double n2 = Math.Sin(2 * Math.PI * 233 * t) * GaussEnv(t, 0.22, 25);
        double n3 = Math.Sin(2 * Math.PI * 196 * t) * GaussEnv(t, 0.36, 25);
        return (n1 + n2 + n3) * 0.5;
    }

    private static double Footstep(double t)
    {
        double thud = Math.Sin(2 * Math.PI * 120 * t) * Math.Exp(-t * 60);
        return thud * 0.35;
    }

    private static double GaussEnv(double t, double center, double sharpness)
        => Math.Exp(-Math.Pow((t - center) * sharpness, 2));

    // ── Construção de WAV PCM 16-bit mono ───────────────────────────────────

    private static byte[] Build(Func<double, double> sampler, double duration)
    {
        int samples  = (int)(duration * SR);
        int dataSize = samples * 2;
        using var ms = new MemoryStream(44 + dataSize);
        using var bw = new BinaryWriter(ms);
        bw.Write(Encoding.ASCII.GetBytes("RIFF"));
        bw.Write(36 + dataSize);
        bw.Write(Encoding.ASCII.GetBytes("WAVEfmt "));
        bw.Write(16); bw.Write((short)1); bw.Write((short)1);
        bw.Write(SR); bw.Write(SR * 2);
        bw.Write((short)2); bw.Write((short)16);
        bw.Write(Encoding.ASCII.GetBytes("data"));
        bw.Write(dataSize);
        for (int i = 0; i < samples; i++)
        {
            double s = Math.Clamp(sampler((double)i / SR), -1.0, 1.0);
            bw.Write((short)(s * 32767));
        }
        return ms.ToArray();
    }

    // ── Reprodução (assíncrona via winmm) ────────────────────────────────────

    private void Play(byte[] wav) =>
        PlaySound(wav, IntPtr.Zero, SND_MEMORY | SND_ASYNC | SND_NODEFAULT);

    public void PlayGunshot()  => Play(_gunshot);
    public void PlayEmpty()    => Play(_empty);
    public void PlayReload()   => Play(_reload);
    public void PlayPickup()   => Play(_pickup);
    public void PlayDamage()   => Play(_damage);
    public void PlayDeath()    => Play(_death);
    public void PlayPoison()   => Play(_poison);
    public void PlayFootstep() => Play(_footstep);
}
