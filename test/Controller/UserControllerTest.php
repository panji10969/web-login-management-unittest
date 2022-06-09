<?php

namespace ProgrammerZamanNow\Belajar\PHP\MVC\Controller
{
    require_once __DIR__ ."/../Helper/helper.php";
    
    use PHPUnit\Framework\TestCase;
    use ProgrammerZamanNow\Belajar\PHP\MVC\Config\Database;
    use ProgrammerZamanNow\Belajar\PHP\MVC\Domain\Session;
    use ProgrammerZamanNow\Belajar\PHP\MVC\Domain\User;
    use ProgrammerZamanNow\Belajar\PHP\MVC\Repository\SessionRepository;
    use ProgrammerZamanNow\Belajar\PHP\MVC\Repository\UserRepository;
    use ProgrammerZamanNow\Belajar\PHP\MVC\Service\SessionService;

    class UserControllerTest extends TestCase
    {
        private UserController $userController;
        private UserRepository $userRepository;
        private SessionRepository $sessionRepository;

        protected function setUp(): void
        {
            $this->userController = new UserController();

            $this->sessionRepository = new SessionRepository(Database::getConnection());
            $this->sessionRepository->deleteAll();

            $this->userRepository = new UserRepository(Database::getConnection());
            $this->userRepository->deleteAll();

            putenv("mode=test");
        }
        
        public function testRegister()
        {
            $this->userController->register();

            $this->expectOutputRegex("[Register]");
            $this->expectOutputRegex("[Id]");
            $this->expectOutputRegex("[Name]");
            $this->expectOutputRegex("[Password]");
            $this->expectOutputRegex("[Register new User]");
        }

        public function testPostRegisterSuccess()
        {
            $_POST['id'] = 'panji';
            $_POST['name'] = 'Panji';
            $_POST['password'] = 'rahasia';

            $this->userController->postRegister();

            $this->expectOutputRegex("[Location: /users/login]");
        }

        public function testPostRegisterValidationError()
        {
            $_POST['id'] = '';
            $_POST['name'] = 'Panji';
            $_POST['password'] = 'rahasia';

            $this->userController->postRegister();

            $this->expectOutputRegex("[Register]");
            $this->expectOutputRegex("[Id]");
            $this->expectOutputRegex("[Name]");
            $this->expectOutputRegex("[Password]");
            $this->expectOutputRegex("[Register new User]");
            $this->expectOutputRegex("[Id, Name, Password can not blank]");
        }

        public function testPostRegisterDuplicate()
        {
            $user = new User();
            $user->id = "panji";
            $user->name = "Panji";
            $user->password = "rahasia";

            $this->userRepository->save($user);
            
            $_POST['id'] = 'panji';
            $_POST['name'] = 'Panji';
            $_POST['password'] = 'rahasia';

            $this->userController->postRegister();

            $this->expectOutputRegex("[Register]");
            $this->expectOutputRegex("[Id]");
            $this->expectOutputRegex("[Name]");
            $this->expectOutputRegex("[Password]");
            $this->expectOutputRegex("[Register new User]");
            $this->expectOutputRegex("[User Id already exist]");
        }

        public function testLogin()
        {
            $this->userController->login();

            $this->expectOutputRegex("[Login user]");
            $this->expectOutputRegex("[Id]");
            $this->expectOutputRegex("[Password]");
        }

        public function testLoginSuccess()
        {
            $user = new User();
            $user->id = "panji";
            $user->name = "Panji";
            $user->password = password_hash("rahasia", PASSWORD_BCRYPT);

            $this->userRepository->save($user);

            $_POST['id'] = "panji";
            $_POST['password'] = "rahasia";
            
            $this->userController->postLogin();

            $this->expectOutputRegex("[Location: /]");
            $this->expectOutputRegex("[X-PZN-SESSION: ]");
        }

        public function testLoginValidationError()
        {
            $_POST['id'] = '';
            $_POST['password'] = '';
            
            $this->userController->postLogin();

            $this->expectOutputRegex("[Login user]");
            $this->expectOutputRegex("[Id]");
            $this->expectOutputRegex("[Password]");
            $this->expectOutputRegex("[Id, Password can not blank]");
        }

        public function testLoginUserNotFound()
        {
            $_POST['id'] = 'notfound';
            $_POST['password'] = 'notfound';
            
            $this->userController->postLogin();

            $this->expectOutputRegex("[Login user]");
            $this->expectOutputRegex("[Id]");
            $this->expectOutputRegex("[Password]");
            $this->expectOutputRegex("[Id or password is wrong]");
        }

        public function testLoginWrongPassword()
        {
            $user = new User();
            $user->id = "panji";
            $user->name = "Panji";
            $user->password = password_hash("rahasia", PASSWORD_BCRYPT);

            $this->userRepository->save($user);

            $_POST['id'] = "panji";
            $_POST['password'] = "salah";
            
            $this->userController->postLogin();

            $this->expectOutputRegex("[Id or password is wrong]");
        }

        public function testLogout()
        {
            $user = new User();
            $user->id = "panji";
            $user->name = "Panji";
            $user->password = password_hash("rahasia", PASSWORD_BCRYPT);
            $this->userRepository->save($user);

            $session = new Session();
            $session->id = uniqid();
            $session->userId = $user->id;
            $this->sessionRepository->save($session);

            $_COOKIE[SessionService::$COOKIE_NAME] = $session->id;

            $this->userController->logout();

            $this->expectOutputRegex("[Location: /]");
            $this->expectOutputRegex("[X-PZN-SESSION: ]");
        }

        public function testUpdateProfile()
        {
            $user = new User();
            $user->id = "panji";
            $user->name = "Panji";
            $user->password = password_hash("rahasia", PASSWORD_BCRYPT);
            $this->userRepository->save($user);

            $session = new Session();
            $session->id = uniqid();
            $session->userId = $user->id;
            $this->sessionRepository->save($session);

            $_COOKIE[SessionService::$COOKIE_NAME] = $session->id;

            $this->userController->updateProfile();

            $this->expectOutputRegex("[Profile]");
            $this->expectOutputRegex("[Id]");
            $this->expectOutputRegex("[panji]");
            $this->expectOutputRegex("[name]");
            $this->expectOutputRegex("[Panji]");
        }

        public function testPostUpdateProfileSuccess()
        {
            $user = new User();
            $user->id = "panji";
            $user->name = "Panji";
            $user->password = password_hash("rahasia", PASSWORD_BCRYPT);
            $this->userRepository->save($user);

            $session = new Session();
            $session->id = uniqid();
            $session->userId = $user->id;
            $this->sessionRepository->save($session);

            $_COOKIE[SessionService::$COOKIE_NAME] = $session->id;

            $_POST['name'] = "yudo";
            $this->userController->postUpdateProfile();

            $this->expectOutputRegex("[Location: /]");

            $result = $this->userRepository->findById("panji");
            self::assertEquals("yudo", $result->name);
        }

        public function testPostUpdateValidationError()
        {
            $user = new User();
            $user->id = "panji";
            $user->name = "Panji";
            $user->password = password_hash("rahasia", PASSWORD_BCRYPT);
            $this->userRepository->save($user);

            $session = new Session();
            $session->id = uniqid();
            $session->userId = $user->id;
            $this->sessionRepository->save($session);

            $_COOKIE[SessionService::$COOKIE_NAME] = $session->id;

            $_POST['name'] = '';
            $this->userController->postUpdateProfile();

            $this->expectOutputRegex("[Profile]");
            $this->expectOutputRegex("[Id]");
            $this->expectOutputRegex("[panji]");
            $this->expectOutputRegex("[name]");
            $this->expectOutputRegex("[Id, Name can not blank]");
        }

        public function testUpdatePassword()
        {
            $user = new User();
            $user->id = "panji";
            $user->name = "Panji";
            $user->password = password_hash("rahasia", PASSWORD_BCRYPT);
            $this->userRepository->save($user);

            $session = new Session();
            $session->id = uniqid();
            $session->userId = $user->id;
            $this->sessionRepository->save($session);

            $_COOKIE[SessionService::$COOKIE_NAME] = $session->id;

            $this->userController->updatePassword();

            $this->expectOutputRegex("[Password]");
            $this->expectOutputRegex("[Id]");
            $this->expectOutputRegex("[panji]");
        }

        public function testPostUpdatePasswordSuccess()
        {
            $user = new User();
            $user->id = "panji";
            $user->name = "Panji";
            $user->password = password_hash("rahasia", PASSWORD_BCRYPT);
            $this->userRepository->save($user);

            $session = new Session();
            $session->id = uniqid();
            $session->userId = $user->id;
            $this->sessionRepository->save($session);

            $_COOKIE[SessionService::$COOKIE_NAME] = $session->id;

            $_POST['oldPassword'] = 'rahasia';
            $_POST['newPassword'] = 'panji';

            $this->userController->postUpdatePassword();

            $this->expectOutputRegex("[Location: /]");

            $result = $this->userRepository->findById($user->id);
            self::assertTrue(password_verify("panji", $result->password));
        }

        public function testPostUpdatePasswordValidationError()
        {
            $user = new User();
            $user->id = "panji";
            $user->name = "Panji";
            $user->password = password_hash("rahasia", PASSWORD_BCRYPT);
            $this->userRepository->save($user);

            $session = new Session();
            $session->id = uniqid();
            $session->userId = $user->id;
            $this->sessionRepository->save($session);

            $_COOKIE[SessionService::$COOKIE_NAME] = $session->id;

            $_POST['oldPassword'] = '';
            $_POST['newPassword'] = '';

            $this->userController->postUpdatePassword();

            $this->expectOutputRegex("[Password]");
            $this->expectOutputRegex("[Id]");
            $this->expectOutputRegex("[panji]");
            $this->expectOutputRegex("[Id, Old Password, New Password can not blank]");
        }

        public function testPostUpdateWrongPassword()
        {
            $user = new User();
            $user->id = "panji";
            $user->name = "Panji";
            $user->password = password_hash("rahasia", PASSWORD_BCRYPT);
            $this->userRepository->save($user);

            $session = new Session();
            $session->id = uniqid();
            $session->userId = $user->id;
            $this->sessionRepository->save($session);

            $_COOKIE[SessionService::$COOKIE_NAME] = $session->id;

            $_POST['oldPassword'] = 'salah';
            $_POST['newPassword'] = 'panji';

            $this->userController->postUpdatePassword();

            $this->expectOutputRegex("[Password]");
            $this->expectOutputRegex("[Id]");
            $this->expectOutputRegex("[panji]");
            $this->expectOutputRegex("[Old password is wrong]");
        }
    }
}

?>