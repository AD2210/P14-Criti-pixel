<?php

declare(strict_types=1);

namespace App\Tests\Functional\Auth;

use App\Model\Entity\User;
use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterTest extends FunctionalTestCase
{
    public function testThatRegistrationShouldSucceeded(): void
    {
        $this->get('/auth/register');

        $this->client->submitForm('S\'inscrire', self::getFormData());

        self::assertResponseRedirects('/auth/login');

        $user = $this->getEntityManager()->getRepository(User::class)->findOneByEmail('user@email.com');

        $userPasswordHasher = $this->service(UserPasswordHasherInterface::class);

        self::assertNotNull($user);
        self::assertSame('username', $user->getUsername());
        self::assertSame('user@email.com', $user->getEmail());
        self::assertTrue($userPasswordHasher->isPasswordValid($user, 'SuperPassword123!'));
    }

    #[DataProvider('provideInvalidFormData')]
    public function testThatRegistrationShouldFailed(array $formData): void
    {
        $this->get('/auth/register');

        $data = array_replace(self::getFormData(), $formData); // On assure l'override avec un array_replace

        $this->client->submitForm('S\'inscrire', $data);

        self::assertResponseIsUnprocessable();
    }

    public static function provideInvalidFormData(): iterable
    {
        yield 'empty username' => [['register[username]' => '']];
        yield 'non unique username' => [['register[username]' => 'user+1']];
        yield 'too long username' => [['register[username]' => 'Lorem ipsum dolor sit amet orci aliquam']];
        yield 'empty email' => [['register[email]' => '']];
        yield 'non unique email' => [['register[email]' => 'user+1@email.com']];
        yield 'invalid email' => [['register[email]' => 'fail']];
    }

    public static function getFormData(array $overrideData = []): array
    {
        return [
            'register[username]' => 'username',
            'register[email]' => 'user@email.com',
            'register[plainPassword]' => 'SuperPassword123!'
        ] + $overrideData;
    }
}
