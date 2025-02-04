import sys
from PySide6.QtCore import Qt
from PySide6.QtWidgets import QApplication, QMainWindow, QMenuBar, QVBoxLayout, QWidget, QLabel
from PySide6.QtGui import QAction

class MainWindow(QMainWindow):
    def __init__(self):
        super().__init__()

        self.setWindowTitle("QMenuBar Showcase")
        self.setGeometry(100, 100, 400, 300)
        central_widget = QWidget(self)
        layout = QVBoxLayout(central_widget)
        self.label = QLabel("Menu bar native status: Unknown", central_widget)
        layout.addWidget(self.label)
        self.setCentralWidget(central_widget)
        self.menu_bar = self.menuBar()
        file_menu = self.menu_bar.addMenu("File")
        toggle_native_action = QAction("Toggle Native Menu Bar", self)
        toggle_native_action.triggered.connect(self.toggle_native_menubar)
        file_menu.addAction(toggle_native_action)
        self.update_native_status()

    def toggle_native_menubar(self):
        is_native = self.menuBar().isNativeMenuBar()
        if is_native:
            self.menuBar().setNativeMenuBar(False)
        else:
            self.menuBar().setNativeMenuBar(True)
        self.update_native_status()

    def update_native_status(self):
        if self.menuBar().isNativeMenuBar():
            self.label.setText("Menu bar native status: Native")
        else:
            self.label.setText("Menu bar native status: Non-native")

if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = MainWindow()
    window.show()
    sys.exit(app.exec())
