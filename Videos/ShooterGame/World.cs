namespace ShooterGame;

public class World
{
    public int Width  { get; }
    public int Height { get; }
    public TileType[,] Tiles { get; }

    public World(int width, int height)
    {
        Width  = width;
        Height = height;
        Tiles  = new TileType[width, height];
        Generate();
    }

    private void Generate()
    {
        // Paredes nas bordas
        for (int x = 0; x < Width;  x++) { Tiles[x, 0] = TileType.Wall; Tiles[x, Height - 1] = TileType.Wall; }
        for (int y = 0; y < Height; y++) { Tiles[0, y] = TileType.Wall; Tiles[Width - 1, y]  = TileType.Wall; }

        // Obstáculos interiores
        var rng = new Random(42);
        for (int i = 0; i < 25; i++)
        {
            int wx  = rng.Next(4, Width  - 4);
            int wy  = rng.Next(4, Height - 4);
            int len = rng.Next(3, 9);
            bool horizontal = rng.Next(2) == 0;
            for (int j = 0; j < len; j++)
            {
                int tx = horizontal ? wx + j : wx;
                int ty = horizontal ? wy     : wy + j;
                if (InBounds(tx, ty) && tx > 1 && tx < Width - 2 && ty > 1 && ty < Height - 2)
                    Tiles[tx, ty] = TileType.Wall;
            }
        }

        // Garante que o spawn do jogador (5,5) está livre
        for (int dx = -1; dx <= 1; dx++)
            for (int dy = -1; dy <= 1; dy++)
                if (InBounds(5 + dx, 5 + dy))
                    Tiles[5 + dx, 5 + dy] = TileType.Empty;
    }

    public bool IsWalkable(int x, int y) =>
        InBounds(x, y) && Tiles[x, y] == TileType.Empty;

    public bool InBounds(int x, int y) =>
        x >= 0 && x < Width && y >= 0 && y < Height;
}
