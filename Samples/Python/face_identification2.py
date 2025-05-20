import sys
import cv2
import numpy as np
import os
from PySide6.QtWidgets import (
    QApplication, QWidget, QPushButton, QLabel, QVBoxLayout, QMessageBox,
    QInputDialog, QLineEdit
)
from PySide6.QtCore import QTimer, Qt
from PySide6.QtGui import QImage, QPixmap
import dlib

class FaceApp(QWidget):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("Face Enrollment App")
        self.setGeometry(100, 100, 800, 600)

        # Buttons
        self.init_btn = QPushButton("Initialize Device")
        self.terminate_btn = QPushButton("Terminate Device")
        self.enroll_btn = QPushButton("Enroll Face")

        # Label for video
        self.image_label = QLabel()
        self.image_label.setAlignment(Qt.AlignCenter)

        self.instruction_label = QLabel("")
        self.instruction_label.setAlignment(Qt.AlignCenter)
        self.timer_label = QLabel("")
        self.timer_label.setAlignment(Qt.AlignCenter)

        # Layout
        layout = QVBoxLayout()
        layout.addWidget(self.image_label)
        layout.addWidget(self.instruction_label)
        layout.addWidget(self.timer_label)
        layout.addWidget(self.init_btn)
        layout.addWidget(self.terminate_btn)
        layout.addWidget(self.enroll_btn)
        self.setLayout(layout)

        # Signals
        self.init_btn.clicked.connect(self.initialize_device)
        self.terminate_btn.clicked.connect(self.terminate_device)
        self.enroll_btn.clicked.connect(self.enroll_face)

        # Internal
        self.cap = None
        self.timer = QTimer()
        self.timer.timeout.connect(self.update_frame)
        self.face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
        self.enrolled_faces = []
        self.face_templates_dir = os.path.join(os.path.dirname(__file__), "face_templates")
        os.makedirs(self.face_templates_dir, exist_ok=True)

        # Dlib face detector and shape predictor
        self.dlib_detector = dlib.get_frontal_face_detector()
        # You may need to download shape_predictor_68_face_landmarks.dat and provide the path
        self.dlib_predictor = None
        predictor_path = os.path.join(os.path.dirname(__file__), "shape_predictor_68_face_landmarks.dat")
        if os.path.exists(predictor_path):
            self.dlib_predictor = dlib.shape_predictor(predictor_path)

        self.enroll_active = False
        self.enroll_prompts = []
        self.enroll_index = 0
        self.enroll_captured = 0
        self.enroll_max = 0
        self.enroll_timer_count = 0
        self.enroll_timer_qtimer = QTimer()
        self.enroll_timer_qtimer.timeout.connect(self.update_enroll_timer_label)
        self.person_name = ""

        # Disable terminate and enroll buttons until camera is initialized
        self.terminate_btn.setEnabled(False)
        self.enroll_btn.setEnabled(False)

    def initialize_device(self):
        # Check for available cameras before opening
        try:
            test_cap = cv2.VideoCapture(0)
            if not test_cap.isOpened():
                QMessageBox.critical(self, "Error", "No camera found or camera index out of range.")
                return
            test_cap.release()
            
            # Initialize camera
            self.cap = cv2.VideoCapture(0)
            if not self.cap.isOpened():
                QMessageBox.critical(self, "Error", "Could not open camera.")
                return

            self.timer.start(30)
            self.init_btn.setEnabled(False)
            self.terminate_btn.setEnabled(True)
            self.enroll_btn.setEnabled(True)
        except Exception as e:
            QMessageBox.critical(self, "Error", f"Failed to initialize device: {str(e)}")
            self.terminate_device()

    def terminate_device(self):
        self.timer.stop()
        if self.cap:
            self.cap.release()
            self.cap = None
        self.image_label.clear()
        self.init_btn.setEnabled(True)
        self.terminate_btn.setEnabled(False)
        self.enroll_btn.setEnabled(False)

    def detect_face(self, frame):
        results = []
        # Use dlib detector
        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        dlib_faces = self.dlib_detector(gray, 1)
        h, w = frame.shape[:2]
        template_files = [f for f in os.listdir(self.face_templates_dir) if f.lower().endswith(('.png', '.jpg', '.jpeg'))]
        for rect in dlib_faces:
            x, y, x2, y2 = rect.left(), rect.top(), rect.right(), rect.bottom()
            x, y = max(0, x), max(0, y)
            w_box, h_box = min(w - x, x2 - x), min(h - y, y2 - y)
            name = ""
            if template_files and w_box > 0 and h_box > 0:
                input_face = cv2.resize(frame[y:y+h_box, x:x+w_box], (100, 100))
                input_gray = cv2.cvtColor(input_face, cv2.COLOR_BGR2GRAY)
                input_hist = cv2.calcHist([input_gray], [0], None, [256], [0, 256])
                input_hist = cv2.normalize(input_hist, input_hist).flatten()
                best_score = 0.0
                best_name = ""
                for template_file in template_files:
                    template_path = os.path.join(self.face_templates_dir, template_file)
                    template_img = cv2.imread(template_path)
                    if template_img is None:
                        continue
                    template_gray = cv2.cvtColor(template_img, cv2.COLOR_BGR2GRAY)
                    template_hist = cv2.calcHist([template_gray], [0], None, [256], [0, 256])
                    template_hist = cv2.normalize(template_hist, template_hist).flatten()
                    score = cv2.compareHist(input_hist, template_hist, cv2.HISTCMP_CORREL)
                    if score > best_score and score > 0.8:
                        best_score = score
                        best_name = os.path.splitext(template_file)[0]
                if best_name:
                    name = best_name
            results.append((x, y, w_box, h_box, name))
        # Fallback to DNN or Haar if no faces found
        if not results:
            h, w = frame.shape[:2]
            if self.face_cascade:
                gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
                faces = self.face_cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5)
                for (x, y, w_box, h_box) in faces:
                    name = ""
                    if template_files:
                        input_face = cv2.resize(frame[y:y+h_box, x:x+w_box], (100, 100))
                        input_gray = cv2.cvtColor(input_face, cv2.COLOR_BGR2GRAY)
                        input_hist = cv2.calcHist([input_gray], [0], None, [256], [0, 256])
                        input_hist = cv2.normalize(input_hist, input_hist).flatten()
                        best_score = 0.0
                        best_name = ""
                        for template_file in template_files:
                            template_path = os.path.join(self.face_templates_dir, template_file)
                            template_img = cv2.imread(template_path)
                            if template_img is None:
                                continue
                            template_gray = cv2.cvtColor(template_img, cv2.COLOR_BGR2GRAY)
                            template_hist = cv2.calcHist([template_gray], [0], None, [256], [0, 256])
                            template_hist = cv2.normalize(template_hist, template_hist).flatten()
                            score = cv2.compareHist(input_hist, template_hist, cv2.HISTCMP_CORREL)
                            if score > best_score and score > 0.9:
                                best_score = score
                                best_name = os.path.splitext(template_file)[0]
                        if best_name:
                            name = best_name
                    results.append((x, y, w_box, h_box, name))
        return results

    def update_frame(self):
        ret, frame = self.cap.read()
        if not ret:
            return
        # Draw bounding boxes and names on detected faces
        faces = self.detect_face(frame)
        for (x, y, w, h, name) in faces:
            cv2.rectangle(frame, (x, y), (x + w, y + h), (0, 255, 0), 2)
            if name:
                cv2.putText(frame, name, (x, y - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.9, (0, 255, 0), 2)
        rgb_image = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        h, w, ch = rgb_image.shape
        bytes_per_line = ch * w
        qt_image = QImage(rgb_image.data, w, h, bytes_per_line, QImage.Format_RGB888)
        self.image_label.setPixmap(QPixmap.fromImage(qt_image))

    def enroll_face(self):
        if not self.cap or self.enroll_active:
            return
            
        # Prompt for person name
        name, ok = QInputDialog.getText(self, "Face Enrollment", 
                                        "Enter the person's name:", QLineEdit.Normal, "")
        if not ok or not name.strip():
            QMessageBox.warning(self, "Face Enrollment", "Name is required for enrollment.")
            return
            
        self.person_name = name.strip()
        self.enroll_prompts = [
            "Neutral face",
            "Smile",
            "Turn head left",
            "Turn head right",
            "Tilt head up",
            "Tilt head down",
            "With glasses (if you wear them)",
            "Without glasses (if you wear them)",
        ]
        self.enroll_max = min(10, len(self.enroll_prompts))
        self.enroll_index = 0
        self.enroll_captured = 0
        self.enroll_active = True
        self.instruction_label.setText(f"Please show: {self.enroll_prompts[self.enroll_index]}")
        self.timer_label.setText("5")
        self.timer.timeout.disconnect()
        self.timer.timeout.connect(self.enroll_update_frame)
        self.enroll_next_capture_time = QTimer()
        self.enroll_next_capture_time.setSingleShot(True)
        self.enroll_next_capture_time.timeout.connect(self.capture_enroll_image)
        self.enroll_timer_count = 5
        self.enroll_timer_qtimer.start(1000)
        self.enroll_next_capture_time.start(500)  # Start quickly for the first prompt

    def enroll_update_frame(self):
        ret, frame = self.cap.read()
        if not ret:
            return
        faces = self.detect_face(frame)
        if len(faces) > 0:
            x, y, w, h = faces[0][:4]
            cv2.rectangle(frame, (x, y), (x + w, y + h), (0, 255, 0), 2)
            cv2.putText(frame, self.enroll_prompts[self.enroll_index], (x, y - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)
        rgb_image = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        h_img, w_img, ch = rgb_image.shape
        bytes_per_line = ch * w_img
        qt_image = QImage(rgb_image.data, w_img, h_img, bytes_per_line, QImage.Format_RGB888)
        self.image_label.setPixmap(QPixmap.fromImage(qt_image))

    def update_enroll_timer_label(self):
        if not self.enroll_active:
            self.timer_label.setText("")
            self.enroll_timer_qtimer.stop()
            return
        if self.enroll_timer_count > 0:
            self.timer_label.setText(str(self.enroll_timer_count))
            self.enroll_timer_count -= 1
        else:
            self.timer_label.setText("")

    def capture_enroll_image(self):
        if not self.enroll_active or self.enroll_index >= self.enroll_max:
            self.instruction_label.setText("")
            self.timer_label.setText("")
            self.enroll_timer_qtimer.stop()
            self.timer.timeout.disconnect()
            self.timer.timeout.connect(self.update_frame)
            if self.enroll_captured > 0:
                QMessageBox.information(self, "Face Enrollment", f"{self.enroll_captured} face images enrolled and saved for {self.person_name}.")
            else:
                QMessageBox.warning(self, "Face Enrollment", "No face images enrolled.")
            self.enroll_active = False
            return

        ret, frame = self.cap.read()
        if not ret:
            self.enroll_timer_count = 5
            self.enroll_timer_qtimer.start(1000)
            self.enroll_next_capture_time.start(5000)
            return
        faces = self.detect_face(frame)
        if len(faces) == 0:
            self.instruction_label.setText(f"No face detected. Please show: {self.enroll_prompts[self.enroll_index]}")
            self.enroll_timer_count = 5
            self.enroll_timer_qtimer.start(1000)
            self.enroll_next_capture_time.start(5000)
            return
        x, y, w, h = faces[0][:4]
        face_img = frame[y:y+h, x:x+w]
        face = cv2.resize(face_img, (100, 100))
        self.enrolled_faces.append(face)
        
        # Use the person name for saving templates
        save_path = os.path.join(self.face_templates_dir, f"{self.person_name}_{self.enroll_index+1}.png")
        cv2.imwrite(save_path, face)
        
        self.enroll_captured += 1
        self.enroll_index += 1
        if self.enroll_index < self.enroll_max:
            self.instruction_label.setText(f"Please show: {self.enroll_prompts[self.enroll_index]}")
            self.enroll_timer_count = 5
            self.enroll_timer_qtimer.start(1000)
            self.enroll_next_capture_time.start(5000)
        else:
            self.instruction_label.setText("Enrollment complete.")
            self.timer_label.setText("")
            self.enroll_timer_qtimer.stop()
            self.enroll_next_capture_time.start(1000)  # Short delay before finishing

    def closeEvent(self, event):
        self.terminate_device()
        event.accept()


if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = FaceApp()
    window.show()
    sys.exit(app.exec())
