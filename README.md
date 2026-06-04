# Hotel Vivandro — Sistema de Gestão de Reservas Hoteleiras

**Projeto Final PDW 2026**
**Aluno:** Vivandro Kambanza — 20241805
**Universidade Europeia**

## Descrição

Aplicação web para a gestão integral de um hotel de pequeno porte.
Permite que clientes pesquisem e reservem quartos, e que a equipa do hotel (gestor e rececionista) administre quartos, reservas, pagamentos, check-in/check-out e relatórios.

## Stack Tecnológica

- **Backend:** PHP 8 nativo (sem frameworks)
- **Base de dados:** MySQL / MariaDB
- **Servidor:** Apache (XAMPP)
- **Frontend:** HTML5, CSS3, JavaScript (vanilla)
- **Versionamento:** Git + GitHub

## Estrutura do Projeto

```text
20241805/
├── backend/
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── book.php
│   ├── my-reservations.php
│   ├── reservation.php
│   ├── about.php
│   ├── ajax_price.php
│   ├── admin/
│   │   ├── index.php
│   │   ├── reservations.php
│   │   ├── reservation-detail.php
│   │   ├── rooms.php
│   │   ├── room-types.php
│   │   ├── guests.php
│   │   ├── users.php
│   │   ├── payments.php
│   │   ├── checkin.php
│   │   ├── reports.php
│   │   ├── logs.php
│   │   └── comprovativo.php
│   ├── includes/
│   │   ├── functions.php
│   │   ├── auth.php
│   │   ├── db.php
│   │   ├── header.php
│   │   ├── footer.php
│   │   ├── admin_header.php
│   │   └── admin_footer.php
│   ├── models/
│   │   ├── UserModel.php
│   │   ├── RoomModel.php
│   │   ├── RoomTypeModel.php
│   │   ├── ReservationModel.php
│   │   └── PaymentModel.php
│   ├── views/
│   │   └── admin/
│   │       ├── dashboard.php
│   │       ├── checkin.php
│   │       ├── guests.php
│   │       ├── payments.php
│   │       ├── reservations.php
│   │       ├── room-types.php
│   │       ├── rooms.php
│   │       └── users.php
│   └── assets/
│       ├── css/style.css
│       └── js/main.js
└── database/
    ├── DatabaseCreate.sql
    ├── DatabasePopulate.sql
    └── DatabaseQueries.sql
```

## Instalação Local (XAMPP)

1. Copiar a pasta `backend/` para `C:\xampp\htdocs\hotel\`
2. Iniciar Apache e MySQL no XAMPP
3. Abrir phpMyAdmin e importar `database/DatabaseCreate.sql`
4. Importar `database/DatabasePopulate.sql`
5. Abrir `http://localhost/hotel/` no browser

## Repositório GitHub

https://github.com/Vivandrokambanza/20241805-hotel.git
