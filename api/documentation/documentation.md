
add-user.php
    input is sent through json. Variables that are sent are: mail, adress, employment_number, birthdate, username, password, type.

    Adress is stored and interpereted as a string.
    Employment_number should only be an int/numbers input.
    Birthdate uses yyyy/mm/dd format.
    Type can either be: admin, end_user or user.

    Returned json can either be Success or Error. 

    Example json input:
        {
        "mail": "customer@example.com",
        "adress": "123 Main St, City, Country",
        "employment_number": "EMP12345",
        "birthdate": "1990-01-01",
        "username": "customer_user",
        "password": "securepassword123",
        "type": "end_user"
        }
    return result:
        end_user customer_user added successfully

    return result error:
        ERROR
    