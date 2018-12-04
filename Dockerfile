FROM alpine:3.3-timezone
MAINTAINER ljfuyuan <ljfuyuan@qq.com>
ADD app /go/bin/app
WORKDIR /go
ENTRYPOINT ["/go/bin/app"]
EXPOSE 8008
