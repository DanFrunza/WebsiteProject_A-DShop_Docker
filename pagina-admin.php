<?php
include("database.php");
session_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>A&DShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="Css/style.css">
</head>
<?php
include("header1.php");
?>
<body>
    <h1 class="admin-title">Admin page</h1>
    <h2 class="admin-title">Inserarea si stergerea produselor</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
    Daca doriti sa modificati adaugati id-ul produsului. Daca doriti sa adaugati un produs nou, lasati campul id-ului gol:<br>
    <input type="number" name="id_produs" id="id_produs"><br>
    Numele produsului:<br>
    <input type="text" name="nume_produs" id="nume_produs"><br>
    Descrierea produsului:<br>
    <input type="text" name="descriere_produs" id="descriere_produs"><br>
    Selectează imaginea pentru încărcare a produsului:<br>
    <input type="file" name="imagine_produs" id="imagine_produs"><br>
    Pretul produsului:<br>
    <input type="text" name="pret_produs" id="pret_produs"><br>
    Categorie: <br>
    <select id="categorie_produs" name="categorie_produs">
        <option value="electronics">Electronics</option>
        <option value="appliances">Appliances</option>
        <option value="office_supplies">Office Supplies</option>
    </select><br>
    <input type="submit" value="Inserare/modificare produs" name="submit">
</form>
<?php

function createInsertProductProcedure($pdo) {
    try {
        
        $dropProcedureSQL = "DROP PROCEDURE IF EXISTS insert_product";
        $pdo->exec($dropProcedureSQL);

        
        $createProcedureSQL = "
        CREATE PROCEDURE insert_product(
            IN p_nume VARCHAR(255),
            IN p_descriere TEXT,
            IN p_imagine LONGBLOB,
            IN p_pret FLOAT,
            IN p_categorie VARCHAR(255)
        )
        BEGIN
            INSERT INTO produse (nume, descriere, imagine, pret, categorie) VALUES (p_nume, p_descriere, p_imagine, p_pret, p_categorie);
        END;
        ";
        $pdo->exec($createProcedureSQL);
    } catch (PDOException $e) {
        
        throw new Exception("Eroare la crearea procedurii: " . $e->getMessage());
    }
}

function createInsertProductTrigger($pdo) {
    try {
        
        $dropTriggerSQL = "DROP TRIGGER IF EXISTS trigger_insert_product";
        $pdo->exec($dropTriggerSQL);

        
        $createTriggerSQL = "
        CREATE TRIGGER trigger_insert_product
        AFTER INSERT ON produse
        FOR EACH ROW
        BEGIN
            INSERT INTO product_insert_log (product_id, product_name, insertion_date)
            VALUES (NEW.id_produs, NEW.nume, NOW());
        END;
        ";
        $pdo->exec($createTriggerSQL);
    } catch (PDOException $e) {
        
        throw new Exception("Eroare la crearea trigger-ului: " . $e->getMessage());
    }
}



createInsertProductProcedure($pdo);
createInsertProductTrigger($pdo);



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_produs = filter_input(INPUT_POST, 'id_produs', FILTER_VALIDATE_INT);
    $nume = filter_input(INPUT_POST, "nume_produs", FILTER_SANITIZE_SPECIAL_CHARS);
    $descriere = filter_input(INPUT_POST, "descriere_produs", FILTER_SANITIZE_SPECIAL_CHARS);
    if (isset($_FILES['imagine_produs']) && !empty($_FILES['imagine_produs']['tmp_name'])) {
        $imagine = file_get_contents($_FILES['imagine_produs']['tmp_name']);
    }
    $pret = filter_input(INPUT_POST, "pret_produs", FILTER_VALIDATE_FLOAT);
    $categorie = filter_input(INPUT_POST, "categorie_produs", FILTER_SANITIZE_SPECIAL_CHARS);

    if ($id_produs !== false && $id_produs !== null) {
        $select_sql = "SELECT * FROM produse WHERE id_produs = :id_produs";
        $stmt = $pdo->prepare($select_sql);
        $stmt->bindParam(':id_produs', $id_produs, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($row) {
            if (!empty($nume) && $nume !== $row["nume"]) {
                $update_sql = "UPDATE produse SET nume = :nume WHERE id_produs = :id_produs";
                $stmt = $pdo->prepare($update_sql);
                $stmt->bindParam(':nume', $nume, PDO::PARAM_STR);
                $stmt->bindParam(':id_produs', $id_produs, PDO::PARAM_INT);
                $stmt->execute();
                echo "<p>Numele produsului a fost modificat.</p>";
            }
            if (!empty($descriere) && $descriere !== $row["descriere"]) {
                $update_sql = "UPDATE produse SET descriere = :descriere WHERE id_produs = :id_produs";
                $stmt = $pdo->prepare($update_sql);
                $stmt->bindParam(':descriere', $descriere, PDO::PARAM_STR);
                $stmt->bindParam(':id_produs', $id_produs, PDO::PARAM_INT);
                $stmt->execute();
                echo "<p>Descrierea produsului a fost modificata.</p>";
            }
            if (!empty($pret) && $pret !== $row["pret"]) {
                $update_sql = "UPDATE produse SET pret = :pret WHERE id_produs = :id_produs";
                $stmt = $pdo->prepare($update_sql);
                $stmt->bindParam(':pret', $pret, PDO::PARAM_INT);
                $stmt->bindParam(':id_produs', $id_produs, PDO::PARAM_INT);
                $stmt->execute();
                echo "<p>Pretul produsului a fost modificat.</p>";
            }
            if (!empty($imagine) && $imagine !== $row["imagine"]) {
                $update_sql = "UPDATE produse SET imagine = :imagine WHERE id_produs = :id_produs";
                $stmt = $pdo->prepare($update_sql);
                $stmt->bindParam(':imagine', $imagine, PDO::PARAM_LOB);
                $stmt->bindParam(':id_produs', $id_produs, PDO::PARAM_INT);
                $stmt->execute();
                echo "<p>Imaginea produsului a fost modificata.</p>";
            }
            if (!empty($categorie) && $categorie !== $row["categorie"]) {
                $update_sql = "UPDATE produse SET categorie = :categorie WHERE id_produs = :id_produs";
                $stmt = $pdo->prepare($update_sql);
                $stmt->bindParam(':categorie', $categorie, PDO::PARAM_STR);
                $stmt->bindParam(':id_produs', $id_produs, PDO::PARAM_INT);
                $stmt->execute();
                echo "<p>Categoria produsului a fost modificata.</p>";
            }
        } else {
            echo "<p class='error-message'>Id invalid.</p>";
        }
    
    
    } else {
        if (!empty($nume) && !empty($imagine) && !empty($pret)) {
            // Prepare the procedure call
            $stmt = $pdo->prepare("CALL insert_product(?, ?, ?, ?, ?)");
            // Bind parameters
            $stmt->bindParam(1, $nume);
            $stmt->bindParam(2, $descriere);
            $stmt->bindParam(3, $imagine, PDO::PARAM_LOB);
            $stmt->bindParam(4, $pret);
            $stmt->bindParam(5, $categorie);
            // Execute the procedure call
            $stmt->execute();

            echo "<p>Produs adăugat cu succes.</p>";
        } else {
            echo "<p class='error-message'>Nume, imagine și preț sunt necesare pentru a adăuga un produs nou.</p>";
        }
    }
}
?>

    <br><br><br><br>
    <h2 class="admin-title">Stergere produs</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
    Daca doriti sa stergeti un produs, adaugatii id-ul:<br>
    <input type="number" name="id_produs1" id="id_produs1"><br>
    <input type="submit" value="Stergere produs" name="submit">
    </form>
    <?php

    function createDeleteProductProcedure($pdo) {
        try {
            
            $dropProcedureSQL = "DROP PROCEDURE IF EXISTS delete_product";
            $pdo->exec($dropProcedureSQL);

           
            $createProcedureSQL = "
            CREATE PROCEDURE delete_product(
                IN p_id INT
            )
            BEGIN
                DELETE FROM produse WHERE id_produs = p_id;
            END;
            ";
            $pdo->exec($createProcedureSQL);
        } catch (PDOException $e) {
            
            throw new Exception("Eroare la crearea procedurii: " . $e->getMessage());
        }
    }

    function createDeleteProductTrigger($pdo) {
        try {
           
            $dropTriggerSQL = "DROP TRIGGER IF EXISTS trigger_delete_product";
            $pdo->exec($dropTriggerSQL);
    
            
            $createTriggerSQL = "
            CREATE TRIGGER trigger_delete_product
            AFTER DELETE ON produse
            FOR EACH ROW
            BEGIN
                INSERT INTO deleted_product_log (product_name, deletion_date)
                VALUES (OLD.nume, NOW());
            END;
            ";
            $pdo->exec($createTriggerSQL);
        } catch (PDOException $e) {
            
            throw new Exception("Eroare la crearea trigger-ului: " . $e->getMessage());
        }
    }

    createDeleteProductProcedure($pdo);
    createDeleteProductTrigger($pdo);


    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id_produs1 = filter_input(INPUT_POST, 'id_produs1', FILTER_VALIDATE_INT);
        if ($id_produs1 !== false && $id_produs1 !== null) {
            try {
                $stmt = $pdo->prepare("CALL delete_product(?)");
                $stmt->bindParam(1, $id_produs1, PDO::PARAM_INT);
                $stmt->execute();
    
                if ($stmt->rowCount() > 0) {
                    echo "Produs șters cu succes.";
                } else {
                    echo "Produsul nu a putut fi șters pentru că nu există în baza de date.";
                }
            } catch (PDOException $e) {
                echo "Produsul nu a putut fi șters: " . $e->getMessage();
            }
        }
    }
    ?>
    <br><br><br>


</body>
</html>
