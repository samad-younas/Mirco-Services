<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{
    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * Display a listing of jobs based on user type or ID.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $userId = $request->get('user_id');
            $authenticatedUser = $request->__authenticatedUser;

            if ($userId) {
                $response = $this->repository->getUsersJobs($userId);
            } elseif ($this->isAdminOrSuperAdmin($authenticatedUser)) {
                $response = $this->repository->getAll($request);
            } else {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch jobs'], 500);
        }
    }

    /**
     * Display the specified job.
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $job = $this->repository->with('translatorJobRel.user')->find($id);

            if (!$job) {
                return response()->json(['error' => 'Job not found'], 404);
            }

            return response()->json($job, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch job'], 500);
        }
    }

    /**
     * Store a newly created job.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $user = $request->__authenticatedUser;

            $response = $this->repository->store($user, $data);

            return response()->json($response, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create job'], 500);
        }
    }

    /**
     * Update the specified job.
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        try {
            $data = array_except($request->all(), ['_token', 'submit']);
            $user = $request->__authenticatedUser;

            $response = $this->repository->updateJob($id, $data, $user);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update job'], 500);
        }
    }

    /**
     * Send immediate job email.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function immediateJobEmail(Request $request)
    {
        try {
            $data = $request->all();
            $response = $this->repository->storeJobEmail($data);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send email'], 500);
        }
    }

    /**
     * Get job history for a user.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|null
     */
    public function getHistory(Request $request)
    {
        try {
            $userId = $request->get('user_id');

            if ($userId) {
                $response = $this->repository->getUsersJobsHistory($userId, $request);
                return response()->json($response, 200);
            }

            return response()->json(['error' => 'User ID required'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch job history'], 500);
        }
    }

    /**
     * Accept a job.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptJob(Request $request)
    {
        try {
            $data = $request->all();
            $user = $request->__authenticatedUser;

            $response = $this->repository->acceptJob($data, $user);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to accept job'], 500);
        }
    }

    /**
     * Check if user is admin or superadmin.
     * @param $user
     * @return bool
     */
    private function isAdminOrSuperAdmin($user)
    {
        return in_array($user->user_type, [env('ADMIN_ROLE_ID'), env('SUPERADMIN_ROLE_ID')]);
    }

    /**
     * Handle job distance feed updates.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function distanceFeed(Request $request)
    {
        try {
            $data = $request->all();

            $this->updateDistance($data);
            $this->updateJobDetails($data);

            return response()->json(['message' => 'Record updated!'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update records'], 500);
        }
    }

    /**
     * Update distance in the database.
     * @param array $data
     * @return void
     */
    private function updateDistance(array $data)
    {
        if (!empty($data['distance']) && !empty($data['jobid'])) {
            Distance::where('job_id', $data['jobid'])->update(['distance' => $data['distance'], 'time' => $data['time'] ?? null]);
        }
    }

    /**
     * Update job details in the database.
     * @param array $data
     * @return void
     */
    private function updateJobDetails(array $data)
    {
        $updates = [
            'admin_comments' => $data['admincomment'] ?? '',
            'flagged' => $data['flagged'] === 'true' ? 'yes' : 'no',
            'manually_handled' => $data['manually_handled'] === 'true' ? 'yes' : 'no',
            'by_admin' => $data['by_admin'] === 'true' ? 'yes' : 'no',
            'session_time' => $data['session_time'] ?? ''
        ];

        if (!empty($data['jobid'])) {
            Job::where('id', $data['jobid'])->update($updates);
        }
    }
}
