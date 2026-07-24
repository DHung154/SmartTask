<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * User Model
 * 
 * Handles all database operations related to users:
 * - Registration (creating new users)
 * - Login (verifying credentials)
 * - Finding users by email or ID
 * 
 * Security Features:
 * - Passwords are hashed using bcrypt (PASSWORD_DEFAULT)
 * - All queries use prepared statements to prevent SQL injection
 */
class User extends Model
{
    /**
     * Find a user by email
     * 
     * Used during login to fetch user data for password verification
     * Also used during registration to check if email already exists
     * 
     * @param string $email User's email address
     * @return array|false User data array or false if not found
     */
    public function findByEmail($email)
    {
        $stmt = $this->getDb()->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * Find a user by ID
     * 
     * Used to fetch user details from session user_id
     * 
     * @param int $id User's primary key ID
     * @return array|false User data array or false if not found
     */
    public function findById($id)
    {
        $stmt = $this->getDb()->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * Create a new user (Registration)
     * 
     * Password Hashing:
     * - Uses PASSWORD_DEFAULT (currently bcrypt)
     * - Automatically handles salt generation
     * - Future-proof: PHP will update the algorithm as needed
     * 
     * @param array $data User data with keys: name, email, password
     * @return bool True on success, false on failure
     */
    public function create($data)
    {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $this->getDb()->prepare("
            INSERT INTO users (name, email, password, created_at) 
            VALUES (:name, :email, :password, NOW())
        ");

        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $hashedPassword);

        return $stmt->execute();
    }

    /**
     * Verify user credentials (Login)
     * 
     * How password verification works:
     * 1. Find user by email
     * 2. Use password_verify() to compare plain text with hash
     * 3. password_verify() handles all the bcrypt comparison securely
     * 
     * @param string $email User's email
     * @param string $password User's password (plain text from form)
     * @return array|false User data if valid, false if invalid
     */
    public function verify($email, $password)
    {
        $user = $this->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    /**
     * Check if email already exists
     *
     * Used during registration to prevent duplicate accounts
     *
     * @param string   $email     Email to check
     * @param int|null $exceptId  B? qua user n�y khi ki?m tra (d�ng l�c s?a h? so,
     *                            v� email hi?n t?i c?a ch�nh h? kh�ng t�nh l� tr�ng)
     * @return bool True if email exists, false if available
     */
    public function emailExists($email, $exceptId = null)
    {
        $user = $this->findByEmail($email);

        if ($user === false) {
            return false;
        }

        // Khi user t? s?a h? so m� gi? nguy�n email th� kh�ng coi l� tr�ng
        if ($exceptId !== null && (int)$user['id'] === (int)$exceptId) {
            return false;
        }

        return true;
    }

    /**
     * C?p nh?t th�ng tin h? so (t�n v� email)
     *
     * Kh�ng d?ng t?i m?t kh?u - d?i m?t kh?u c� h�m ri�ng b�n du?i
     * d? tr�nh v� t�nh ghi d� hash b?ng chu?i r?ng.
     *
     * @param int   $id   User ID
     * @param array $data ['name' => ..., 'email' => ...]
     * @return bool
     */
    public function updateProfile($id, $data)
    {
        $stmt = $this->getDb()->prepare("
            UPDATE users
            SET name = :name, email = :email
            WHERE id = :id
        ");

        return $stmt->execute([
            ':name'  => $data['name'],
            ':email' => $data['email'],
            ':id'    => $id
        ]);
    }

    /**
     * �?i m?t kh?u
     *
     * Luu � b?o m?t: h�m n�y CH? ghi m?t kh?u m?i.
     * Vi?c ki?m tra m?t kh?u cu ph?i l�m ? controller b?ng verifyPassword()
     * tru?c khi g?i h�m n�y - tr�nh tru?ng h?p ai d� chi?m du?c session
     * r?i d?i m?t kh?u m� kh�ng c?n bi?t m?t kh?u cu.
     *
     * @param int    $id          User ID
     * @param string $newPassword M?t kh?u m?i d?ng ch? thu?ng (chua hash)
     * @return bool
     */
    public function updatePassword($id, $newPassword)
    {
        // password_hash t? sinh salt, m?i l?n g?i ra m?t hash kh�c nhau
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $this->getDb()->prepare("
            UPDATE users
            SET password = :password
            WHERE id = :id
        ");

        return $stmt->execute([
            ':password' => $hashed,
            ':id'       => $id
        ]);
    }

    /**
     * Ki?m tra m?t kh?u hi?n t?i c?a m?t user c� d�ng kh�ng
     *
     * D�ng tru?c khi cho ph�p d?i m?t kh?u ho?c c�c thao t�c nh?y c?m.
     *
     * @param int    $id       User ID
     * @param string $password M?t kh?u ngu?i d�ng v?a g�
     * @return bool
     */
    public function verifyPassword($id, $password)
    {
        $user = $this->findById($id);

        if (!$user) {
            return false;
        }

        return password_verify($password, $user['password']);
    }
}
