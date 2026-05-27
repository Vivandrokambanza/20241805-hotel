using Microsoft.Data.Sqlite;
using System.Security.Cryptography;
using System.Text;

namespace ShooterGame;

public sealed class Database : IDisposable
{
    private readonly SqliteConnection _conn;

    public Database()
    {
        // Guarda o ficheiro em %LocalAppData%\ShooterGame\players.db
        string dir = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "ShooterGame");
        Directory.CreateDirectory(dir);
        string path = Path.Combine(dir, "players.db");

        _conn = new SqliteConnection($"Data Source={path}");
        _conn.Open();
        CreateTables();
    }

    private void CreateTables()
    {
        Exec(@"
            CREATE TABLE IF NOT EXISTS users (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                username      TEXT    UNIQUE NOT NULL COLLATE NOCASE,
                password_hash TEXT    NOT NULL,
                salt          TEXT    NOT NULL,
                created_at    TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
                high_score    INTEGER NOT NULL DEFAULT 0,
                total_kills   INTEGER NOT NULL DEFAULT 0,
                total_games   INTEGER NOT NULL DEFAULT 0
            );");
    }

    // ── Registo ───────────────────────────────────────────────────────────────
    public (bool ok, string error) Register(string username, string password)
    {
        if (username.Length < 3 || username.Length > 20)
            return (false, "Username deve ter entre 3 e 20 caracteres.");
        if (!username.All(c => char.IsLetterOrDigit(c) || c == '_'))
            return (false, "Username so pode ter letras, numeros e '_'.");
        if (password.Length < 6)
            return (false, "Password deve ter pelo menos 6 caracteres.");

        byte[] salt     = RandomNumberGenerator.GetBytes(32);
        string saltB64  = Convert.ToBase64String(salt);
        string hashB64  = Convert.ToBase64String(HashPassword(password, salt));

        try
        {
            using var cmd = _conn.CreateCommand();
            cmd.CommandText = "INSERT INTO users (username, password_hash, salt) VALUES ($u,$h,$s)";
            cmd.Parameters.AddWithValue("$u", username);
            cmd.Parameters.AddWithValue("$h", hashB64);
            cmd.Parameters.AddWithValue("$s", saltB64);
            cmd.ExecuteNonQuery();
            return (true, "");
        }
        catch (SqliteException ex) when (ex.SqliteErrorCode == 19)
        {
            return (false, "Esse username ja existe. Escolhe outro.");
        }
    }

    // ── Login ─────────────────────────────────────────────────────────────────
    public (UserAccount? user, string error) Login(string username, string password)
    {
        if (string.IsNullOrWhiteSpace(username)) return (null, "Introduz o username.");
        if (string.IsNullOrWhiteSpace(password)) return (null, "Introduz a password.");

        using var cmd = _conn.CreateCommand();
        cmd.CommandText = @"
            SELECT id, username, password_hash, salt,
                   high_score, total_kills, total_games, created_at
            FROM   users WHERE username = $u COLLATE NOCASE";
        cmd.Parameters.AddWithValue("$u", username);

        using var r = cmd.ExecuteReader();
        if (!r.Read()) return (null, "Username nao encontrado.");

        byte[] salt       = Convert.FromBase64String(r.GetString(3));
        byte[] stored     = Convert.FromBase64String(r.GetString(2));
        byte[] input      = HashPassword(password, salt);

        if (!CryptographicOperations.FixedTimeEquals(stored, input))
            return (null, "Password incorreta.");

        return (new UserAccount
        {
            Id         = r.GetInt32(0),
            Username   = r.GetString(1),
            HighScore  = r.GetInt32(4),
            TotalKills = r.GetInt32(5),
            TotalGames = r.GetInt32(6),
            CreatedAt  = r.GetString(7)
        }, "");
    }

    // ── Guarda resultado de jogo ──────────────────────────────────────────────
    public void SaveGameResult(int userId, int kills, int score)
    {
        using var cmd = _conn.CreateCommand();
        cmd.CommandText = @"
            UPDATE users SET
                total_kills = total_kills + $k,
                total_games = total_games + 1,
                high_score  = MAX(high_score, $s)
            WHERE id = $id";
        cmd.Parameters.AddWithValue("$k",  kills);
        cmd.Parameters.AddWithValue("$s",  score);
        cmd.Parameters.AddWithValue("$id", userId);
        cmd.ExecuteNonQuery();
    }

    // ── Hash PBKDF2 (seguro para passwords) ───────────────────────────────────
    private static byte[] HashPassword(string password, byte[] salt)
    {
        using var kdf = new Rfc2898DeriveBytes(
            Encoding.UTF8.GetBytes(password), salt, 100_000, HashAlgorithmName.SHA256);
        return kdf.GetBytes(32);
    }

    private void Exec(string sql)
    {
        using var cmd = _conn.CreateCommand();
        cmd.CommandText = sql;
        cmd.ExecuteNonQuery();
    }

    public void Dispose() => _conn.Dispose();
}
