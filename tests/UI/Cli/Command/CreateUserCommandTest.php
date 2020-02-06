<?php

declare(strict_types=1);

namespace App\Tests\UI\Cli\Command;

use App\Application\Query\Item;
use App\Application\Query\User\FindByEmail\FindByEmailQuery;
use App\Infrastructure\User\Query\Projections\UserView;
use App\Tests\UI\Cli\AbstractConsoleTestCase;
use App\UI\Cli\Command\CreateUserCommand;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class CreateUserCommandTest extends AbstractConsoleTestCase
{
    /**
     * @test
     *
     * @group unit
     *
     * @throws \Exception
     * @throws \Assert\AssertionFailedException
     */
    public function command_integration_with_bus_success(): void
    {
        $email = 'jorge.arcoma@gmail.com';

        /** @var MessageBusInterface $commandBus */
        $commandBus = $this->service('messenger.bus.command');
        $commandTester = $this->app($command = new CreateUserCommand($commandBus), 'app:create-user');

        $commandTester->execute([
            'command'  => $command->getName(),
            'uuid'     => Uuid::uuid4()->toString(),
            'email'    => $email,
            'password' => 'jorgepass',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('User Created:', $output);
        $this->assertStringContainsString('Email: jorge.arcoma@gmail.com', $output);

        $stamp = $this->ask(new FindByEmailQuery($email))->last(HandledStamp::class);

        /** @var Item $userItem */
        $userItem = $stamp->getResult();

        /** @var UserView $userRead */
        $userRead = $userItem->readModel;

        self::assertInstanceOf(Item::class, $userItem);
        self::assertInstanceOf(UserView::class, $userRead);
        self::assertSame($email, $userRead->email());
    }
}
