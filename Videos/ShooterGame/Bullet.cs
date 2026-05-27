namespace ShooterGame;

public class Bullet
{
    public int  X          { get; set; }
    public int  Y          { get; set; }
    public int  Dx         { get; }
    public int  Dy         { get; }
    public bool FromPlayer { get; }

    public Bullet(int x, int y, int dx, int dy, bool fromPlayer)
    {
        X = x; Y = y; Dx = dx; Dy = dy; FromPlayer = fromPlayer;
    }
}
