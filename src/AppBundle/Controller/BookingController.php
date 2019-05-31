<?php
namespace AppBundle\Controller;

use Pimcore\Mail;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Quest;
use Pimcore\Model\Document\Email;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class BookingController extends ApiController
{
    /**
     * @Route("/booking/{questId}/events/{eventId}.json", name="app_booking_book_event", methods={"POST"}, requirements={"questId"="\d+","eventId"="\d+"})
     * @SWG\Tag(name="Booking")
     * @SWG\Response(
     *     response=201,
     *     description=""
     * )
     * @param Request $request
     * @throws \Exception
     */
    public function bookEvent(Request $request)
    {
        $quest = Quest::getById($request->get('questId'));
        if ($quest === null) {
            throw new NotFoundHttpException('booking.error.quest.not_found');
        }

        $user = $this->getUser();
        if (!\Pimcore::inDebugMode() && $user !== $quest->getUser()) {
            throw new UnauthorizedHttpException('booking.error.not_authorized');
        }

        if (\Pimcore::inDebugMode()) {
            $user = $quest->getUser();
        }

        $eventId = intval($request->get('eventId'));
        $eventMetadata = null;

        /** @var ObjectMetadata $objectMetadata */
        foreach ($quest->getEvents() as $objectMetadata) {
            if ($objectMetadata->getElement()->getId() === $eventId) {
                $eventMetadata = $objectMetadata;
                break;
            }
        }

        if ($eventMetadata === null) {
            throw new NotFoundHttpException('booking.error.event.not_found');
        }

        $data = $eventMetadata->getData();
        if (false && isset($data['booked']) && $data['booked'] === '1') {
            throw new NotFoundHttpException('booking.error.event.already_booked');
        }

        $data['booked'] = true;
        $eventMetadata->setData($data);

        $this->sendConfirmation($user, $eventMetadata);
        $quest->save();
    }

    /**
     * @param $user
     * @param $eventMetadata
     * @throws \Exception
     */
    public function sendConfirmation($user, $eventMetadata): void
    {
        $emailDocument = Email::getByPath('/auth/bookingConfirmation');
        if ($emailDocument === null) {
            throw new \Exception('Booking confirmation document not set!');
        }

        $email = new Mail();
        $email->setDocument($emailDocument);
        $email->setTo($user->getEmail());
        $email->setParams([
            'eventName' => $eventMetadata->getElement()->getName(),
        ]);

        $email->send();
    }
}