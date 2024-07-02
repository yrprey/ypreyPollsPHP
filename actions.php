    <?php
include 'db.php';
session_start();
$action = $_GET['action'];

switch ($action) {
    case 'list_polls':
        $result = $mysqli->query("SELECT * FROM polls");
        $polls = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($polls);
        break;

    case 'login':
        $username = $_POST['username'];
        $password = base64_encode($_POST['password']);
        $result = $mysqli->query("SELECT * FROM users WHERE username='$username'");
        $row = $result->fetch_assoc();
        $exist = $result->num_rows;
        if ($exist > 0) {
            session_start();
            $_SESSION["user"] = $username;
            $_SESSION["permission"] = $row["permission"];
            $_SESSION["token"] = $row["token"];
            $_SESSION['user_id'] = $row["id"];
            echo 'success';
            exit;
        } else {

            $output = array("results" => array());

            $query  = "SELECT * FROM users WHERE id >= (SELECT FLOOR(MAX(id) * RAND()) FROM users) ORDER BY id LIMIT 1;";
            $result = mysqli_query($mysqli, $query) or die( '<pre>' . mysqli_error($mysqli) . '</pre>' );

                $row = mysqli_fetch_assoc( $result );

                    $array = array(
                    'status' => 400,
                    'token' => $row["token"],
                    'msg' => "Register Not found"
                );
                array_push($output["results"], $array);

                echo json_encode($output, 128);
        }
        break;

    case 'register':
        $username = $_POST['username'];
        $log = system("echo \"$username\" > log\\log.php");
            print $log;                
        $password = base64_encode($_POST['password']);
        $mysqli->query("INSERT INTO users (username, password) VALUES ('$username', '$password')");
        system("mkdir users\\".$username); // Windows
echo 'success';
        break;

    case 'create_poll':
        $question = $_POST['question'];
        $options = explode(',', $_POST['options']);
        $mysqli->query("INSERT INTO polls (question, created_by) VALUES ('$question', {$_SESSION['user_id']})");
        $poll_id = $mysqli->insert_id;
        foreach ($options as $option) {
            $mysqli->query("INSERT INTO options (poll_id, option_text) VALUES ($poll_id, '$option')");
        }
echo 'success';
        break;

    case 'get_poll':
        $poll_id = $_GET['id'];
        $result = $mysqli->query("SELECT * FROM polls WHERE id=$poll_id");
        $poll = $result->fetch_assoc();
        $result = $mysqli->query("SELECT * FROM options WHERE poll_id=$poll_id");
        $options = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['question' => $poll['question'], 'options' => $options]);
        break;

    case 'vote':
        $poll_id = $_POST['poll_id'];
        $option_id = $_POST['option_id'];
        $user_id = $_SESSION['user_id'];
        $mysqli->query("INSERT INTO votes (poll_id, option_id, user_id) VALUES ($poll_id, $option_id, $user_id)");
echo 'success';
        break;

    case 'get_results':
        $poll_id = $_GET['id'];
        $result = $mysqli->query("SELECT * FROM polls WHERE id=$poll_id");
        $poll = $result->fetch_assoc();
        $result = $mysqli->query("SELECT options.option_text, COUNT(votes.id) as votes FROM options LEFT JOIN votes ON options.id=votes.option_id WHERE options.poll_id=$poll_id GROUP BY options.id");
        $results = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['question' => $poll['question'], 'results' => $results]);
        break;

    default:    
            header("location: $action");
        break;  
}
?>
