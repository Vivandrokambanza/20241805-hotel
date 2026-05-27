namespace ShooterGame;

public class Item
{
    public int      X        { get; }
    public int      Y        { get; }
    public ItemType Type     { get; }
    public bool     IsActive { get; set; } = true;

    public Item(int x, int y, ItemType type) { X = x; Y = y; Type = type; }

    public char Symbol => Type switch
    {
        ItemType.RedDrink => 'r',
        ItemType.BlueDrink => 'b',
        ItemType.AmmoPack  => 'a',
        _ => '?'
    };
}
