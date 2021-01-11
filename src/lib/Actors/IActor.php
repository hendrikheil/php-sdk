<?php

namespace Dapr\Actors;

/**
 * Interface IActor
 * @package Dapr
 */
interface IActor
{
    /**
     * Return the ID of the actor, passed via the constructor.
     * @return mixed
     */
    function get_id(): mixed;

    /**
     * Handle a reminder
     *
     * @param string $name The name of the reminder
     * @param mixed $data The data from passed when the reminder was setup
     */
    function remind(string $name, $data): void;

    /**
     * Called when the actor is activated
     */
    function on_activation(): void;

    /**
     * Called when the actor is deactivated
     */
    function on_deactivation(): void;

    /**
     * Deletes a timer
     *
     * @param string $name The name of the timer to delete
     *
     * @return bool
     */
    function delete_timer(string $name): bool;

    /**
     * Creates a new timer, which lasts until the actor is deactivated.
     *
     * @param Timer $timer
     *
     * @return bool
     */
    function create_timer(
        Timer $timer
    ): bool;

    /**
     * Deletes a reminder
     *
     * @param string $name The name of the reminder to delete
     *
     * @return bool Whether the deletion was successful
     */
    function delete_reminder(string $name): bool;

    /**
     * Get a reminder by name
     *
     * @param string $name The name of the reminder
     *
     * @return Reminder|null Information about the reminder
     */
    function get_reminder(string $name): Reminder|null;

    /**
     * Create a new reminder that will wake up the actor.
     *
     * @param Reminder $reminder
     *
     * @return bool Whether the reminder was successfully created
     */
    function create_reminder(
        Reminder $reminder
    ): bool;
}