<?php

namespace VentureDrake\LaravelCrm\Traits;

trait NotifyToast
{
    /**
     * Queues up a notification in either the browser or via a redirect.
     *
     * @param  string  $message
     * @param  string  $route
     * @return void|\Symfony\Component\HttpFoundation\Response
     */
    public function notify($message, $route = null, $routeParams = [], $level = 'success')
    {
        if ($route) {
            session()->flash('notify.message', $message);
            session()->flash('notify.level', $level);

            return redirect()->route($route, $routeParams);
        }

        $this->dispatchBrowserEvent('notifyToast', [
            'message' => $message,
            'level' => $level,
        ]);
    }
}
