    +-----------------+
    |     User        |
    +-----------------+
    | id (PK)         |
    | username        |
    | email (unique)  |
    | roles (json)    |
    | password        |
    | createdAt       |
    | updatedAt       |
    +-----------------+
            | 1
            |
            |
           *
    +-----------------+
    |     Task        |
    +-----------------+
    | id (PK)         |
    | title           |
    | content         |
    | isDone          |
    | author_id (FK)  |
    | createdAt       |
    | updatedAt       |
    +-----------------+
