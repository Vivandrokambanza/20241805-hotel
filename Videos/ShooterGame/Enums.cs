namespace ShooterGame;

public enum Direction   { Up, Down, Left, Right }
public enum ItemType    { RedDrink, BlueDrink, AmmoPack }
public enum TileType    { Empty, Wall }
public enum GameState   { Playing, GameOver, Win }

public static class DirectionExtensions
{
    public static (int dx, int dy) ToVector(this Direction d) => d switch
    {
        Direction.Up    => ( 0, -1),
        Direction.Down  => ( 0,  1),
        Direction.Left  => (-1,  0),
        Direction.Right => ( 1,  0),
        _               => ( 0,  0)
    };
}
