<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class ModerationController extends BaseController
{
    public function index()
    {
        if (!in_array(session()->get('role'), ['reviewer', 'superadmin'])) {
            return redirect()->to('admin/dashboard')->with('error', 'Unauthorized access.');
        }

        $db = \Config\Database::connect();
        
        // Fetch pending reports. (In the future, you will JOIN this with your comments/forum tables to show the actual text)
        $reports = $db->table('reported_content')
            ->select('reported_content.*, users.first_name, users.last_name')
            ->join('users', 'users.id = reported_content.reporter_id')
            ->where('reported_content.status', 'pending')
            ->orderBy('created_at', 'ASC')
            ->get()->getResultArray();

        return view('admin/moderation/flagged', [
            'title' => 'Flagged Content',
            'reports' => $reports
        ]);
    }

    public function resolve($reportId)
    {
        if (!in_array(session()->get('role'), ['reviewer', 'superadmin'])) return $this->response->setStatusCode(403);

        $action = $this->request->getPost('action'); // 'delete_content' or 'dismiss_flag'
        $db = \Config\Database::connect();

        $db->table('reported_content')->where('id', $reportId)->update([
            'status' => $action === 'delete_content' ? 'deleted' : 'dismissed',
            'resolved_at' => date('Y-m-d H:i:s'),
            'resolved_by' => session()->get('user_id')
        ]);

        // Note: If action == 'delete_content', you will also need to run a query here 
        // to actually delete or hide the item from the `comments` or `forum_posts` table!

        return redirect()->back()->with('success', 'Report resolved successfully.');
    }
}