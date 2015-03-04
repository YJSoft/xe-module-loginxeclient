# 로그인XE 클라이언트 모듈
XE의 로그인을 확장하세요! LoginXE 클라이언트 모듈입니다.

# 출품작에 대한 상세 설명
XE로 제작한 사이트에 네이버 계정과 Github 계정으로 로그인/가입/연동할 수 있는 기능을 추가해 주는 모듈입니다.
로그인XE 서버 모듈과 연동하여 사용하시면 됩니다.

# 기능 열거와 특장점
* 네이버 계정과 Github 계정으로 쉽게 회원가입할 수 있습니다.
* 이제 더이상 귀찮게 똑같은 정보를 입력하고 또 입력할 필요가 없습니다.
* 기존 계정에 네이버 계정이나 Github 계정을 연동 및 연동 해제할 수 있습니다
* 위젯(별도 설치)을 통해 현재 연동된 계정을 보고, 쉽게 연동 및 연동 해제할 수 있습니다
설치 및 설정 방법

[설치 방법 링크](http://yjsoft.selfnick.com/?/entry/LoginXE-Client-%EB%AA%A8%EB%93%88-%EC%84%A4%EC%A0%95-%EB%B0%A9%EB%B2%95-%EB%B0%8F-%EC%82%AC%EC%9A%A9%EB%B2%95)를 참고하세요.
 
_설치 후 설정을 하지 않거나 Provider를 체크하지 않으시면 사용하실 수 없습니다. 링크를 참고해서 설정 작업을 마치신 후 사용하세요._
# 업데이트 내역

## Version 1.05.rc1
* 오류 발생시 페이지가 바뀌는 문제 수정
* 가입 페이지의 스킨을 못 불러오는 문제 수정
* 모든 Provider를 체크 해제할 수 없는 문제 수정
* 계정 연동 페이지에 연동 가능한 계정만 표시하도록 수정
* 계정 연동 페이지에 연동 가능한 계정이 없을 경우 연동할 수 있는 계정이 없음을 알림
* 가입 약관 미사용시 로그인XE로 가입이 불가능할 수도 있던 문제 수정
* 일정 시간 초과후 가입시 가입이 불가능할 수 있는 문제 수정

## Version 1.05.beta1
* 전체적인 구조 수정
* 아이디가 숫자로 시작하는 경우 자동으로 lxe를 붙임으로 가입이 가능하도록 수정하였습니다.
* 로그인XE로 가입할 경우 약관 동의 절차를 거칠 수 없는 문제점에 따라 로그인XE를 통한 가입을 차단할 수 있도록 하였습니다.
* (!베타!) 로그인 XE로 회원 가입시 설정한 회원가입 약관을 출력하며, 사용할 비밀번호를 입력 받습니다. 단, 이 기능은 베타로, 현재는 약관동의 안함 등 오류 발생시 자동으로 이전 페이지로 돌아가지 않는 문제점이 있습니다.
* 베타 버전으로 쉬운 설치를 지원하지 않습니다.
* 버그가 있는 버전으로 _사용을 권장하지 않습니다._

## Version 1.04
* 일부 서버에서 is_loginxeonly 관련 오류 나는 문제 수정(관리자 화면에서 업데이트 해주세요)
* 회원정보 페이지에 로그인XE 연동 메뉴 추가(역시 관리자 화면에서 모듈 업데이트를 해주셔야 합니다)
* 기타 버그 수정

## Version 1.03
* 사용중이 아닌 Provider 호출시 오류처리 강화
* 다국어 추가(영어)
* 사용하지 않는 이미지 삭제(로그인 버튼 이미지)
* 기타 버그 수정

## Version 1.02
* 버그 수정

## Version 1.01
* 서버 모듈과 클라이언트 모듈 둘 다 하위 디렉토리(/xe)에 설치되어 있으면 잘못된 주소로 이동할 수 있는 문제 수정

# 업데이트 예정
## Version 1.1
* LoginXE로 로그인한 회원 전체보기, 강제 연동해제 기능