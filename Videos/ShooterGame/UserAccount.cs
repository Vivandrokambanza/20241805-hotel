namespace ShooterGame;

public class UserAccount
{
    public int    Id         { get; set; }
    public string Username   { get; set; } = "";
    public int    HighScore  { get; set; }
    public int    TotalKills { get; set; }
    public int    TotalGames { get; set; }
    public string CreatedAt  { get; set; } = "";
}
