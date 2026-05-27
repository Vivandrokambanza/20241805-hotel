using ShooterGame;

Console.Title          = "Call of Duty: Consola Wars";
Console.OutputEncoding = System.Text.Encoding.UTF8;

// Base de dados (SQLite em %LocalAppData%\ShooterGame\players.db)
using var db = new Database();

// Ecrã de autenticação
var auth = new AuthScreen(db);
var user = auth.Show();

if (user == null)
{
    // Utilizador escolheu Sair no menu de login
    Console.Clear();
    Console.WriteLine("Ate a proxima, soldado!");
    return;
}

// Iniciar jogo com a conta autenticada
Console.Clear();
using var engine = new GameEngine(db, user);
engine.Run();
